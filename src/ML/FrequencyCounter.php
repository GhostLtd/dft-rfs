<?php

namespace App\ML;

class FrequencyCounter
{
    /** @var array<string,int> */
    protected array $frequencies;
    protected ?float $threshold;

    public function __construct()
    {
        $this->frequencies = [];
        $this->threshold = null;
    }

    public function getFrequencies(): array
    {
        return $this->frequencies;
    }

    public function seen(string $label): void
    {
        $this->frequencies[$label] =
            ($this->frequencies[$label] ?? 0) + 1;

        $this->threshold = null;
    }

    public function getClassificationThreshold(): float
    {
        if ($this->threshold === null) {
            $mostCommon = max($this->frequencies);
            $this->threshold = max($mostCommon / 100, 10);
        }

        return $this->threshold;
    }

    /**
     * Returns the labels for which we will learn to classify the data, and their corresponding frequencies.
     *
     * See:
     *   https://cloud.google.com/vertex-ai/docs/text-data/classification/prepare-data#best_practices_for_text_data_used_to_train_models
     *
     *   "The model works best when there are at most 100 times more documents for the most common
     *    label than for the least common label. We recommend removing very low frequency labels."
     *
     *
     *   https://cloud.google.com/vertex-ai/docs/text-data/classification/prepare-data
     *
     *   "You must apply each label to at least 10 documents."
     *
     * ----
     *
     * Additionally, low frequency data-points may also cause AutoML to error with a "Missing labels in training/test/eval split" message
     *
     * See: https://stackoverflow.com/a/64665988/865429
     */
    public function isAboveThreshold(string $label): bool
    {
        $threshold = $this->getClassificationThreshold();
        return $this->frequencies[$label] >= $threshold;
    }
}