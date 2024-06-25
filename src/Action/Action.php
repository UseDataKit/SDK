<?php

namespace DataKit\DataViews\Action;

use JsonException;

final class Action {
	private const TYPE_URL = 'url';
	private const TYPE_MODAL = 'modal';

	private string $type;
	private string $id;
	private string $label;
	private string $icon = '';
	private string $url = '';

	private bool $is_destructive = false;

	private function __construct( string $id, string $label ) {
		$this->id    = $id;
		$this->label = $label;
	}

	public static function modal( string $id, string $label, string $url ): self {
		$action       = new self( $id, $label );
		$action->type = self::TYPE_MODAL;
		$action->url  = $url;

		return $action;
	}

	public static function url( string $id, string $label, string $url ): self {
		$action       = new self( $id, $label );
		$action->type = self::TYPE_URL;
		$action->url  = $url;

		return $action;
	}

	public function primary( string $icon ): self {
		$action       = clone $this;
		$action->icon = $icon;

		return $action;
	}

	public function secondary(): self {
		$action       = clone $this;
		$action->icon = '';

		return $action;
	}

	public function destructive(): self {
		$action                 = clone $this;
		$action->is_destructive = true;

		return $action;
	}

	public function not_destructive(): self {
		$action                 = clone $this;
		$action->is_destructive = false;

		return $action;
	}


	public function to_array(): array {
		$result = [
			'id'            => $this->id,
			'label'         => $this->label,
			'isPrimary'     => $this->icon !== '',
			'isDestructive' => $this->is_destructive,
			'icon'          => $this->icon,
			'callback'      => $this->callback(),
		];

		if ( $this->type === self::TYPE_MODAL ) {
			$result['RenderModal'] = $this->render_modal();
		}

		return $result;
	}

	/**
	 * Function that returns the action callback.
	 * @since $ver$
	 * @return string|null The callback.
	 */
	private function callback(): ?string {
		if ( $this->type !== self::TYPE_URL ) {
			return null;
		}

		try {
			$callback = sprintf(
				'( data ) => %s(data, %s)',
				'datakit_dataviews_actions.url',
				json_encode( $this->context(), JSON_THROW_ON_ERROR ),
			);
		} catch ( JsonException $e ) {
			return '';
		}

		return '__RAW__' . $callback . '__ENDRAW__';
	}

	private function render_modal(): string {
		return 'Modal';
	}

	private function context(): array {
		return [
			'id'  => $this->id,
			'url' => $this->url,
		];
	}
}
