<?php declare(strict_types=1);

namespace Shopware\Storefront\Test\Theme;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Test\Theme\fixtures\ThemeFixtures;
use Shopware\Storefront\Theme\StorefrontPluginRegistry;
use Shopware\Storefront\Theme\ThemeEntity;
use Shopware\Storefront\Theme\ThemeLifecycleService;
use Shopware\Storefront\Theme\ThemeService;

class ThemeTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var ThemeService
     */
    protected $themeService;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var EntityRepositoryInterface
     */
    private $themeRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themeService = $this->getContainer()->get(ThemeService::class);
        $this->themeRepository = $this->getContainer()->get('theme.repository');
        $this->context = Context::createDefaultContext();
    }

    public function testDefaultThemeConfig()
    {
        /** @var ThemeEntity $theme */
        $theme = $this->themeRepository->search(new Criteria(), $this->context)->first();
        $themeConfiguration = $this->themeService->getThemeConfiguration($theme->getId(), false, $this->context);

        $themeConfigFix = ThemeFixtures::getThemeConfig();
        foreach ($themeConfigFix['fields'] as $key => $field) {
            if ($field['type'] === 'media') {
                $themeConfigFix['fields'][$key]['value'] = $themeConfiguration['fields'][$key]['value'];
            }
        }
        static::assertEquals($themeConfigFix, $themeConfiguration);
    }

    public function testDefaultThemeConfigTranslated()
    {
        $theme = $this->themeRepository->search(new Criteria(), $this->context)->first();
        $themeConfiguration = $this->themeService->getThemeConfiguration($theme->getId(), true, $this->context);

        static::assertGreaterThan(0, count($themeConfiguration));

        foreach ($themeConfiguration['fields'] as $item) {
            static::assertStringNotContainsString('sw-theme', $item['label']);
        }
    }

    public function testDefaultThemeConfigFields()
    {
        $theme = $this->themeRepository->search(new Criteria(), $this->context)->first();

        $theme = $this->themeService->getThemeConfigurationFields($theme->getId(), false, $this->context);
        static::assertEquals(ThemeFixtures::getThemeFields(), $theme);
    }

    public function testInheritedThemeConfig()
    {
        $id = Uuid::randomHex();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', StorefrontPluginRegistry::BASE_THEME_NAME));

        /** @var ThemeEntity $baseTheme */
        $baseTheme = $this->themeRepository->search($criteria, $this->context)->first();

        $name = $this->createTheme($baseTheme);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));

        /** @var ThemeEntity $childTheme */
        $childTheme = $this->themeRepository->search($criteria, $this->context)->first();

        $this->themeService->updateTheme(
            $childTheme->getId(),
            [
                'sw-color-brand-primary' => [
                    'value' => '#ff00ff',
                ],
            ],
            null,
            $this->context
        );

        $theme = $this->themeService->getThemeConfiguration($childTheme->getId(), false, $this->context);
        $themeInheritedConfig = ThemeFixtures::getThemeInheritedConfig();

        foreach ($themeInheritedConfig['fields'] as $key => $field) {
            if ($field['type'] === 'media') {
                $themeInheritedConfig['fields'][$key]['value'] = $theme['fields'][$key]['value'];
            }
        }

        static::assertEquals($themeInheritedConfig, $theme);
    }

    public function testCompileTheme()
    {
        static::markTestSkipped('theme compile is not possible cause app.js does not exists');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', StorefrontPluginRegistry::BASE_THEME_NAME));

        /** @var ThemeEntity $baseTheme */
        $baseTheme = $this->themeRepository->search($criteria, $this->context)->first();

        $name = $this->createTheme($baseTheme);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));

        /** @var ThemeEntity $childTheme */
        $childTheme = $this->themeRepository->search($criteria, $this->context)->first();

        $this->themeService->updateTheme(
            $childTheme->getId(),
            [
                'sw-color-brand-primary' => [
                    'value' => '#ff00ff',
                ],
            ],
            null,
            $this->context
        );

        $themeCompiled = $this->themeService->assignTheme($childTheme->getId(), Defaults::SALES_CHANNEL, $this->context);

        static::assertTrue($themeCompiled);
    }

    public function testRefreshPlugin()
    {
        /** @var IdSearchResult $themes */
        $themes = $this->themeRepository->searchIds(new Criteria(), $this->context);
        //static::assertSame(0, $themes->getTotal());
        $themeLifecycleService = $this->getContainer()->get(ThemeLifecycleService::class);
        $themeLifecycleService->refreshThemes($this->context);
        $themes = $this->themeRepository->search(new Criteria(), $this->context);

        static::assertCount(1, $themes->getElements());
        /** @var ThemeEntity $theme */
        $theme = $themes->first();
        static::assertSame('Storefront', $theme->getTechnicalName());
        static::assertNotEmpty($theme->getLabels());
    }

    /**
     * @throws \Exception
     */
    private function createTheme(ThemeEntity $baseTheme): string
    {
        $name = 'test' . Uuid::randomHex();

        $id = Uuid::randomHex();
        $this->themeRepository->create(
            [
                [
                    'id' => $id,
                    'parentThemeId' => $baseTheme->getId(),
                    'name' => $name,
                    'createdAt' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    'configValues' => $baseTheme->getConfigValues(),
                    'baseConfig' => $baseTheme->getBaseConfig(),
                    'description' => $baseTheme->getDescription(),
                    'author' => $baseTheme->getAuthor(),
                    'labels' => $baseTheme->getLabels(),
                    'customFields' => $baseTheme->getCustomFields(),
                    'previewMediaId' => $baseTheme->getPreviewMediaId(),
                ],
            ],
            $this->context
        );

        return $name;
    }
}
