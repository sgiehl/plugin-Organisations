<?php
/**
 * Piwik PRO - Premium functionality and enterprise-level support for Piwik Analytics
 *
 * @link http://piwik.pro
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Organisations\tests\Integration;

use Piwik\Plugin;
use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Plugins\Organisations\Model;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Plugin\Manager as PluginManager;
use Piwik\Db;

use Piwik\Cache as PiwikCache;

/**
 * @group Plugins
 * @group Organisations
 */
class ModelTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();

        PluginManager::getInstance()->loadPlugin('Organisations');
    }

    private $organisation1 = array(
        'name'     => 'test orgl',
        'ipranges' => array(
            '127.0.0.1',
            '192.168.2.0/24',
            '2001:5c0:1000:b::90f8/128'
        )
    );

    public function testAddingAdditionalColumn()
    {
        $plugin = PluginManager::getInstance()->getLoadedPlugin('Organisations');
        $this->clearPluginDimensions($plugin);
        $this->assertFalse($this->hasOrganisationColumn());
        $plugin->install();
        $this->assertTrue($this->hasOrganisationColumn());
    }

    private function setupOrganisation()
    {
        $model = new Model();
        $model->deleteOrganisation(1);
        $model->createOrganisation($this->organisation1);
    }

    public function testCreateDeleteOrganisation()
    {
        $this->setupOrganisation();
        $model = new Model();
        $result = $model->getOrganisation(1);
        $this->assertArraySubset($this->organisation1, $result);
        $model->deleteOrganisation(1);
        $result = $model->getOrganisation(1);
        $this->assertEmpty($result);
    }

    public function testUpdateOrganisation()
    {
        $this->setupOrganisation();
        $model = new Model();
        $result = $model->getOrganisation(1);
        $this->assertArraySubset($this->organisation1, $result);
        $newOrgData = array(
            'name'     => 'updated org',
            'ipranges' => array(
                '145.5.3.34/8',
            )
        );
        $model->updateOrganisation(1, $newOrgData);
        $result = $model->getOrganisation(1);
        $this->assertArraySubset($newOrgData, $result);
    }

    private function clearPluginDimensions(Plugin $plugin)
    {
        foreach (VisitDimension::getDimensions($plugin) as $dimension) {
            $dimension->uninstall();
        }
    }

    private function hasOrganisationColumn()
    {
        foreach (Db::fetchAll('DESC log_visit') as $org) {
            if ($org['Field'] == 'organisation') {
                return true;
            }
        }

        return false;
    }
}