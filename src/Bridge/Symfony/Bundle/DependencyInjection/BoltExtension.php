<?php

declare(strict_types=1);

namespace Bolt\Bridge\Symfony\Bundle\DependencyInjection;

use Bolt\Controller\ErrorController;
use Bolt\Repository\ResetPasswordRequestRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class BoltExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $this->prependApiPlatformBundleConfigurationToExtension($container);
        $this->prependPhpTranslationTranslationBundleConfigurationToExtension($container);
        $this->prependSymfonyFrameworkBundleConfigurationToExtension($container);
        $this->prependSymfonyWebpackEncoreBundleConfigurationToExtension($container);
        $this->prependSymfonycastsResetPasswordBundleConfigurationToExtension($container);
        $this->prependTwigBundleConfigurationToExtension($container);
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // $configuration = new Configuration();
        // $config = $this->processConfiguration($configuration, $configs);

        // $this->registerApiPlatformConfiguration($container, $config);
    }

    // private function registerApiPlatformConfiguration(ContainerBuilder $container, array $config): void
    // {
    //     if ($config['sitename']) {
    //         $container->setParameter('bolt.sitename', $config['sitename']);
    //     }

    //     // dump($container->getParameterBag())

    //     // $container->setAlias('api_platform.operation_path_resolver.default', $config['default_operation_path_resolver']);
    // }

    private function prependApiPlatformBundleConfigurationToExtension(ContainerBuilder $container): void
    {
        if (isset($container->getExtensions()['api_platform'])) {
            $config['enable_swagger_ui'] = false;
            $config['graphql']['graphiql']['enabled'] = true;
            $config['mapping']['paths'] = [
                '%kernel.project_dir%/vendor/bolt/core/src/Entity',
                '%kernel.project_dir%/src/Entity',
            ];
            $config['formats']['jsonld']['mime_types'] = ['application/ld+json'];
            $config['formats']['json']['mime_types'] = ['application/json'];
            $config['formats']['jsonapi']['mime_types'] = ['application/vnd.api+json'];
            $config['formats']['html']['mime_types'] = ['text/html'];
            $config['collection']['pagination']['client_items_per_page'] = true;
            $config['collection']['pagination']['items_per_page_parameter_name'] = 'pageSize';

            $container->prependExtensionConfig('api_platform', $config);
        }
    }

    private function prependPhpTranslationTranslationBundleConfigurationToExtension(ContainerBuilder $container): void
    {
        if (isset($container->getExtensions()['translation'])) {
            $config['webui']['enabled'] = true;
            $config['edit_in_place']['enabled'] = false;
            // $config['configs']['bolt']['dirs'] = ['%kernel.project_dir%/templates', '%kernel.project_dir%/src'];
            // $config['configs']['bolt']['output_dir'] = '%kernel.project_dir%/translations';

            $container->prependExtensionConfig('translation', $config);
        }
    }

    private function prependSymfonyFrameworkBundleConfigurationToExtension(ContainerBuilder $container): void
    {
        if (isset($container->getExtensions()['framework'])) {
            $config['assets']['json_manifest_path'] = '%kernel.project_dir%/%bolt.public_folder%/assets/manifest.json';
            $config['cache']['prefix_seed'] = 'bolt';
            $config['csrf_protection']['enabled'] = true;

            if (method_exists(ErrorController::class, 'showAction')) {
                $config['error_controller'] = ErrorController::class . '::showAction';
            }

            $config['esi']['enabled'] = true;
            $config['fragments']['enabled'] = true;
            $config['http_method_override'] = true;
            $config['session']['handler_id'] = null;
            $config['session']['cookie_lifetime'] = 1209600;
            $config['session']['cookie_secure'] = 'auto';
            $config['validation']['email_validation_mode'] = 'html5';
            $config['validation']['enable_annotations'] = true;

            $container->prependExtensionConfig('framework', $config);
        }
    }

    private function prependSymfonyWebpackEncoreBundleConfigurationToExtension(ContainerBuilder $container): void
    {
        if (isset($container->getExtensions()['webpack_encore'])) {
            $config['output_path'] = '%kernel.project_dir%/%bolt.public_folder%/assets';

            $container->prependExtensionConfig('webpack_encore', $config);
        }
    }

    private function prependSymfonycastsResetPasswordBundleConfigurationToExtension(ContainerBuilder $container): void
    {
        if (isset($container->getExtensions()['symfonycasts_reset_password'])) {
            if (class_exists(ResetPasswordRequestRepository::class)) {
                $config['request_password_repository'] = ResetPasswordRequestRepository::class;
            }

            $container->prependExtensionConfig('symfonycasts_reset_password', $config);
        }
    }

    private function prependTwigBundleConfigurationToExtension(ContainerBuilder $container): void
    {
        if (isset($container->getExtensions()['twig'])) {
            $config['debug'] = '%kernel.debug%';
            $config['default_path'] = '%kernel.project_dir%/vendor/bolt/core/templates';
            $config['exception_controller'] = null;
            $config['form_themes'] = [
                '@bolt/form/layout.html.twig',
                '@bolt/form/fields.html.twig',
            ];
            $config['globals']['config'] = '@Bolt\Configuration\Config';
            $config['globals']['defaultLocale'] = '%locale%';
            $config['paths']['%kernel.project_dir%/vendor/bolt/core/templates/'] = 'bolt';
            if (is_dir('%kernel.project_dir%/public/theme/%bolt.theme%')) {
                $config['paths']['%kernel.project_dir%/public/theme/%bolt.theme%'] = 'theme';
            }
            $config['strict_variables'] = '%kernel.debug%';

            $container->prependExtensionConfig('twig', $config);
        }
    }
}
