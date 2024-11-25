<?php

declare(strict_types=1);

namespace DbToolsBundle\PackFaker\Tests\Functional\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class FakerAddressAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'my_street_address' => 'string',
                'my_secondary_address' => 'string',
                'my_postal_code' => 'string',
                'my_locality' => 'string',
                'my_region' => 'string',
                'my_country' => 'string',
            ],
            [
                [
                    'id' => '1',
                    'my_street_address' => 'Rue Aristide Briand',
                    'my_secondary_address' => 'La maison aux volets bleus',
                    'my_postal_code' => '44400',
                    'my_locality' => 'REZE',
                    'my_region' => 'Pays de loire',
                    'my_country' => 'FRANCE',
                ],
                [
                    'id' => '2',
                    'my_street_address' => 'Rue Jean Jaures',
                    'my_secondary_address' => 'Au dernier étage',
                    'my_postal_code' => '44000',
                    'my_locality' => 'Toto-les-bains',
                    'my_region' => 'Pays de loire',
                    'my_country' => 'FRANCE',
                ],
                [
                    'id' => '3',
                ],
            ],
        );
    }

    public function testCreateWithoutLocalRaiseError(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'faker.address',
            new Options([
                //'locale' => 'fr_FR', // No locale!
                'street_address' => 'my_street_address',
            ])
        ));

        self::expectExceptionMessageMatches("/'locale' must be set/");
        $anonymizator->anonymize();
    }

    public function testAnonymize(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'faker.address',
            new Options([
                'locale' => 'fr_FR',
                'street_address' => 'my_street_address',
                'secondary_address' => 'my_secondary_address',
                'postal_code' => 'my_postal_code',
                'locality' => 'my_locality',
                'region' => 'my_region',
                'country' => 'my_country',
            ])
        ));

        self::assertSame(
            "Rue Aristide Briand",
            $this->getDatabaseSession()->executeQuery('select my_street_address from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select * from table_test order by id asc')->fetchAllAssociative();
        self::assertNotNull($datas[0]);
        self::assertNotSame('Rue Aristide Briand', $datas[0]['my_street_address']);
        self::assertNotSame('La maison aux volets bleus', $datas[0]['my_secondary_address']);
        self::assertNotSame('44400', $datas[0]['my_postal_code']);
        self::assertNotSame('REZE', $datas[0]['my_locality']);
        self::assertNotSame('Pays de loire', $datas[0]['my_region']);
        self::assertNotNull($datas[1]);
        self::assertNotSame('Rue Jean Jaures', $datas[1]['my_street_address']);
        self::assertNotSame('Au dernier étage', $datas[1]['my_secondary_address']);
        self::assertNotSame('44000', $datas[1]['my_postal_code']);
        self::assertNotSame('Toto-les-bains', $datas[1]['my_locality']);
        self::assertNotSame('Pays de loire', $datas[1]['my_region']);
        self::assertCount(3, \array_unique(\array_map(fn ($value) => \serialize($value), $datas)), 'All generated values are different.');
    }

    public function testAnonymizeWithoutRegionSupport(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'faker.address',
            new Options([
                'locale' => 'en_US', // This locale has no region support.
                'street_address' => 'my_street_address',
                'secondary_address' => 'my_secondary_address',
                'postal_code' => 'my_postal_code',
                'locality' => 'my_locality',
                'region' => 'my_region',
                'country' => 'my_country',
            ])
        ));

        self::assertSame(
            "Rue Aristide Briand",
            $this->getDatabaseSession()->executeQuery('select my_street_address from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select * from table_test order by id asc')->fetchAllAssociative();
        self::assertNotNull($datas[0]);
        self::assertNotSame('Rue Aristide Briand', $datas[0]['my_street_address']);
        self::assertNotSame('La maison aux volets bleus', $datas[0]['my_secondary_address']);
        self::assertNotSame('44400', $datas[0]['my_postal_code']);
        self::assertNotSame('REZE', $datas[0]['my_locality']);
        self::assertSame('', $datas[0]['my_region']);
        self::assertNotNull($datas[1]);
        self::assertNotSame('Rue Jean Jaures', $datas[1]['my_street_address']);
        self::assertNotSame('Au dernier étage', $datas[1]['my_secondary_address']);
        self::assertNotSame('44000', $datas[1]['my_postal_code']);
        self::assertNotSame('Toto-les-bains', $datas[1]['my_locality']);
        self::assertSame('', $datas[1]['my_region']);
        self::assertCount(3, \array_unique(\array_map(fn ($value) => \serialize($value), $datas)), 'All generated values are different.');
    }
}
