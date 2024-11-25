<?php

declare(strict_types=1);

namespace DbToolsBundle\PackFaker\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractMultipleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use Faker;

#[AsAnonymizer(
    name: 'address',
    pack: 'faker',
    description: <<<TXT
    Anonymize a mutlicolumn postal address.
    Map columns for each part with options ('country', 'locality', 'region', 'postal_code', 'street_address', 'secondary_address').
    You must set the 'locale' parameter (ex. 'fr_FR', 'en_US', 'de_AT', ...).
    You can also specify the sample table size using the 'sample_size' option
    (default is 500). The more samples you have, the less duplicates you will
    end up with.
    TXT,
    requires: [Faker\Factory::class],
    dependencies: ['fakerphp/faker'],
)]
class FakerAddressAnonymizer extends AbstractMultipleColumnAnonymizer
{
    #[\Override]
    protected function getColumnNames(): array
    {
        return [
            'street_address',
            'secondary_address',
            'postal_code',
            'locality',
            'region',
            'country',
        ];
    }

    #[\Override]
    protected function validateOptions(): void
    {
        parent::validateOptions();

        if (!$this->options->has('locale')) {
            throw new \InvalidArgumentException("'locale' must be set (ex. 'fr_FR', 'en_US', 'de_AT', ...");
        }
    }

    #[\Override]
    protected function hasSampleSizeOption(): bool
    {
        return true;
    }

    #[\Override]
    protected function getSamples(): array
    {
        $sampleSize = $this->getSampleSize();
        $faker = Faker\Factory::create($this->options->get('locale', null, true));

        $supportsSecondaryAddress = $supportsRegion = true;

        try {
            // @phpstan-ignore-next-line
            $faker->secondaryAddress();
        } catch (\InvalidArgumentException) {
            $supportsSecondaryAddress = false;
        }

        try {
            // @phpstan-ignore-next-line
            $faker->region();
        } catch (\InvalidArgumentException) {
            $supportsRegion = false;
        }

        // Importante notice: when faker does not support some methods, depending
        // upon the locale, we simply set an empty string, because sample table
        // columns are not nullable.
        // @todo Should this constraint be kept?
        $ret = [];
        for ($i = 0; $i < $sampleSize; ++$i) {
            $ret[] = [
                $faker->streetAddress(),
                // @phpstan-ignore-next-line
                $supportsSecondaryAddress ? $faker->secondaryAddress() : '',
                $faker->postcode(),
                $faker->city(),
                // @phpstan-ignore-next-line
                $supportsRegion ? $faker->region() : '',
                $faker->country(),
            ];
        }
        return $ret;
    }
}
