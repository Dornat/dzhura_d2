<?php

namespace App\Discord\SlashCommands\Settings;

use Discord\Builders\Components\Component;
use Discord\Builders\Components\SelectMenu;
use LengthException;
use function Discord\poly_strlen;

class SelectMenuRoles extends SelectMenu
{
    public const TYPE_SELECT_MENU_ROLES = 6;
    private string $custom_id;
    private ?string $placeholder;
    private ?int $min_values;
    private ?int $max_values;

    /**
     * @var bool|null
     */
    private $disabled;

    /**
     * Creates a new select menu.
     *
     * @param string|null $custom_id The custom ID of the select menu. If not given, an UUID will be used
     */
    public function __construct(?string $custom_id)
    {
        parent::__construct($custom_id);
        $this->setCustomId($custom_id ?? $this->generateUuid());
    }

    /**
     * Creates a new select menu.
     *
     * @param string|null $custom_id The custom ID of the select menu.
     *
     * @return self
     */
    public static function new(?string $custom_id = null): self
    {
        return new self($custom_id);
    }

    /**
     * Sets the custom ID for the select menu.
     *
     * @param string $custom_id
     *
     * @return $this
     * @throws LengthException
     *
     */
    public function setCustomId($custom_id): self
    {
        if (poly_strlen($custom_id) > 100) {
            throw new LengthException('Custom ID must be maximum 100 characters.');
        }

        $this->custom_id = $custom_id;

        return $this;
    }

    /**
     * Sets the placeholder string to display if nothing is selected.
     * Maximum 150 characters. Null to clear placeholder.
     *
     * @param string|null $placeholder
     *
     * @throws LengthException
     *
     * @return $this
     */
    public function setPlaceholder(?string $placeholder): self
    {
        if (isset($placeholder) && poly_strlen($placeholder) > 150) {
            throw new LengthException('Placeholder string must be less than or equal to 150 characters.');
        }

        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Sets the minimum number of options which must be chosen.
     * Default 1, minimum 0 and maximum 25. Null to set as default.
     *
     * @param int|null $min_values
     *
     * @throws LengthException
     *
     * @return $this
     */
    public function setMinValues(?int $min_values): self
    {
        if (isset($min_values) && ($min_values < 0 || $min_values > 25)) {
            throw new LengthException('Number must be between 0 and 25 inclusive.');
        }

        $this->min_values = $min_values;

        return $this;
    }

    /**
     * Sets the maximum number of options which must be chosen.
     * Default 1 and maximum 25. Null to set as default.
     *
     * @param int|null $max_values
     *
     * @throws LengthException
     *
     * @return $this
     */
    public function setMaxValues(?int $max_values): self
    {
        if ($max_values && $max_values > 25) {
            throw new LengthException('Number must be less than or equal to 25.');
        }

        $this->max_values = $max_values;

        return $this;
    }

    /**
     * Sets the select menus disabled state.
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Returns the Custom ID of the select menu.
     *
     * @return string
     */
    public function getCustomId(): string
    {
        return $this->custom_id;
    }

    /**
     * Returns the placeholder string of the select menu.
     *
     * @return string|null
     */
    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * Returns the minimum number of options that must be selected.
     *
     * @return int|null
     */
    public function getMinValues(): ?int
    {
        return $this->min_values;
    }

    /**
     * Returns the maximum number of options that must be selected.
     *
     * @return int|null
     */
    public function getMaxValues(): ?int
    {
        return $this->max_values;
    }

    /**
     * Returns wether the select menu is disabled.
     *
     * @return bool|null
     */
    public function isDisabled(): ?bool
    {
        return $this->disabled;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => self::TYPE_SELECT_MENU_ROLES,
            'custom_id' => $this->custom_id,
        ];

        if (isset($this->placeholder)) {
            $content['placeholder'] = $this->placeholder;
        }

        if (isset($this->min_values)) {
            $content['min_values'] = $this->min_values;
        }

        if ($this->max_values) {
            $content['max_values'] = $this->max_values;
        }

        if ($this->disabled) {
            $content['disabled'] = true;
        }

        return [
            'type' => Component::TYPE_ACTION_ROW,
            'components' => [$content],
        ];
    }
}