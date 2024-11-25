<?php

declare(strict_types=1);

namespace DbToolsBundle\PackFaker\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use Faker;

#[AsAnonymizer(
    name: 'method',
    pack: 'faker',
    description: <<<TXT
    Anonymize using any faker method, method being provided as the 'method' option.
    You can set the 'locale' parameter (ex. 'fr_FR', 'en_US', 'de_AT', ...).
    You can also specify the sample table size using the 'sample_size' option
    (default is 500). The more samples you have, the less duplicates you will
    end up with.
    TXT,
    requires: [Faker\Factory::class],
    dependencies: ['fakerphp/faker'],
)]
class FakerMethodAnonymizer extends AbstractEnumAnonymizer
{
    private ?Faker\Generator $generator;

    protected function getFakerGenerator(): Faker\Generator
    {
        return $this->generator ??= Faker\Factory::create($this->options->get('locale', Faker\Factory::DEFAULT_LOCALE));
    }

    #[\Override]
    protected function validateOptions(): void
    {
        parent::validateOptions();

        $method = $this->options->get('method', null, true);
        try {
            $this->getFakerGenerator()->{$method}();
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(\sprintf("'%s' option is not a valid faker method.", $method), 0, $e);
        }
    }

    #[\Override]
    protected function hasSampleSizeOption(): bool
    {
        return true;
    }

    #[\Override]
    protected function getSample(): array
    {
        $sampleSize = $this->getSampleSize();
        $faker = $this->getFakerGenerator()->unique(true);
        $method = $this->options->get('method', null, true);

        $ret = [];
        for ($i = 0; $i < $sampleSize; ++$i) {
            $ret[] = $faker->{$method}();
        }
        return $ret;
    }
}
