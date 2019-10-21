<?php

declare(strict_types=1);

namespace LauLamanApps\ApplePassbook\MetaData\Field;

use LauLamanApps\ApplePassbook\Style\NumberStyle;
use LogicException;

final class NumberField extends Field
{
    /**
     * @var string|null
     */
    private $currencyCode;

    /**
     * @var NumberStyle|null
     */
    private $numberStyle;

    public function __construct(string $key, $value, ?string $label = null)
    {
        if (!is_numeric($value)) {
            throw new LogicException('Value should be numeric.');
        }

        parent::__construct($key, $value, $label);
    }

    public function setCurrencyCode(string $currencyCode): void
    {
        if ($this->numberStyle) {
            throw new LogicException('You can not set both a \'currencyCode\' and a \'numberStyle\'. Please set only one of the 2.');
        }

        $this->currencyCode = $currencyCode;
    }

    public function setNumberStyle(NumberStyle $numberStyle): void
    {
        if ($this->currencyCode) {
            throw new LogicException('You can not set both a \'currencyCode\' and a \'numberStyle\'. Please set only one of the 2.');
        }

        $this->numberStyle = $numberStyle;
    }

    public function getMetadata(): array
    {
        $data = parent::getMetadata();
        if ($this->currencyCode) {
            $data['currencyCode'] = $this->currencyCode;
        }

        if ($this->numberStyle) {
            $data['numberStyle'] = $this->numberStyle;
        }

        return $data;
    }
}
