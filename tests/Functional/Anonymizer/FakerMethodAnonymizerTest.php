<?php

declare(strict_types=1);

namespace DbToolsBundle\PackFaker\Tests\Functional\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class FakerMethodAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'my_value' => 'string',
            ],
            [
                [
                    'id' => '1',
                    'my_value' => 'foo',
                ],
                [
                    'id' => '2',
                    'my_value' => 'bar',
                ],
                [
                    'id' => '3',
                ],
            ],
        );
    }

    public function testCreateWithUnknownMethodRaiseError(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'faker.method',
            new Options([
                'method' => 'thisMethodDoesNotAndWillNeverExist',
            ])
        ));

        self::expectExceptionMessageMatches("/is not a valid faker method./");
        $anonymizator->anonymize();
    }

    public function testAnonymize(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'my_value',
            'faker.method',
            new Options([
                'method' => 'ean13',
            ])
        ));

        self::assertSame(
            "foo",
            $this->getDatabaseSession()->executeQuery('select my_value from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select * from table_test order by id asc')->fetchAllAssociative();
        self::assertNotNull($datas[0]);
        self::assertNotSame('foo', $datas[0]['my_value']);
        self::assertNotNull($datas[1]);
        self::assertNotSame('bar', $datas[1]['my_value']);
        self::assertCount(3, \array_unique(\array_map(fn ($value) => \serialize($value), $datas)), 'All generated values are different.');
    }
}
