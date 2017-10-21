<?php

namespace Luthfi\CrudGenerator\Generators;

/**
* Model Policy Generator Class
*/
class ModelPolicyGenerator extends BaseGenerator
{
    /**
     * {@inheritDoc}
     */
    public function generate()
    {
        $parentDirectory = '';
        if (! is_null($this->command->option('parent'))) {
            $parentDirectory = '/'.$this->command->option('parent');
        }
        $modelPolicyPath = $this->makeDirectory(app_path('Policies'.$parentDirectory));

        $this->generateFile(
            $modelPolicyPath.'/'.$this->modelNames['model_name'].'Policy.php',
            $this->getContent()
        );

        $this->command->info($this->modelNames['model_name'].' model policy generated.');

        $this->updateAuthServiceProviderClass();
    }

    /**
     * {@inheritDoc}
     */
    protected function getContent()
    {
        $stub = $this->files->get(__DIR__.'/../stubs/model-policy.stub');

        $policyFileContent = $this->replaceStubString($stub);

        $userModel = config('auth.providers.users.model');

        if ('App\User' !== $userModel) {
            $policyFileContent = str_replace('App\User', $userModel, $policyFileContent);
        }

        if (! is_null($parentName = $this->command->option('parent'))) {
            $policyFileContent = str_replace(
                'App\Policies;',
                "App\Policies\\{$parentName};",
                $policyFileContent
            );
        }

        return $policyFileContent;
    }

    /**
     * Update AuthServiceProviderClass based on created model policy object
     *
     * @return void
     */
    public function updateAuthServiceProviderClass()
    {
        $modelName = $this->modelNames['model_name'];
        $fullModelName = $this->modelNames['full_model_name'];
        $authSPPath = $this->makeAuthServiceProvilderFile(app_path('Providers'), 'AuthServiceProvider.php');

        $authSPContent = $this->files->get($authSPPath);

        if (! is_null($parentName = $this->command->option('parent'))) {
            $modelName = $parentName.'\\'.$modelName;
        }

        $authSPContent = str_replace(
            "    protected \$policies = [\n",
            "    protected \$policies = [\n        '{$fullModelName}' => 'App\Policies\\{$modelName}Policy',\n",
            $authSPContent
        );

        $this->generateFile($authSPPath, $authSPContent);

        $this->command->info('AuthServiceProvider class has been updated.');
    }

    /**
     * Create AuthServiceProvider class if not exists
     * @param  string $routeDirPath Absolute directory path
     * @param  string $filename     File name to be created
     * @return string               Absolute path of create route file
     */
    protected function makeAuthServiceProvilderFile($routeDirPath, $filename)
    {
        $routeDirPath = $this->makeDirectory($routeDirPath);

        if (! $this->files->exists($routeDirPath.'/'.$filename)) {
            $this->generateFile(
                $routeDirPath.'/'.$filename,
                $this->files->get(__DIR__.'/../stubs/AuthServiceProvider.stub')
            );
        }

        return $routeDirPath.'/'.$filename;
    }
}