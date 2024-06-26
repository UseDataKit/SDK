<?php

namespace DataKit\DataViews\Action;

use JsonException;

final class Action {
	private const TYPE_URL = 'url';
	private const TYPE_AJAX = 'ajax';
	private const TYPE_MODAL = 'modal';

	private string $type;
	private string $id;
	private string $label;
	private string $icon = '';

	private bool $is_destructive = false;
	private bool $is_header_hidden = false;
	private bool $is_bulk = false;

	private array $context = [];

	private function __construct( string $id, string $label ) {
		$this->id    = $id;
		$this->label = $label;
	}

	public static function modal( string $id, string $label, string $url ) : self {
		$action                 = new self( $id, $label );
		$action->type           = self::TYPE_MODAL;
		$action->context['url'] = $url;

		return $action;
	}

	public static function url( string $id, string $label, string $url ) : self {
		$action       = new self( $id, $label );
		$action->type = self::TYPE_URL;

		$action->context['url'] = $url;

		return $action;
	}

	public static function ajax(
		string $id,
		string $label,
		string $url,
		string $method = 'GET',
		array $params = [],
		bool $use_single_request = false
	) : self {
		$action       = new self( $id, $label );
		$action->type = self::TYPE_AJAX;

		$action->context['url']                = $url;
		$action->context['method']             = $method;
		$action->context['params']             = $params;
		$action->context['use_single_request'] = $use_single_request;

		return $action;
	}

	public function confirm( ?string $message ) : self {
		$action                     = clone $this;
		$action->context['confirm'] = $message;

		return $action;
	}

	public function primary( string $icon ) : self {
		$action       = clone $this;
		$action->icon = $icon;

		return $action;
	}

	public function secondary() : self {
		$action       = clone $this;
		$action->icon = '';

		return $action;
	}

	public function destructive() : self {
		$action                 = clone $this;
		$action->is_destructive = true;

		return $action;
	}

	public function not_destructive() : self {
		$action                 = clone $this;
		$action->is_destructive = false;

		return $action;
	}

	public function hide_header() {
		$action                   = clone $this;
		$action->is_header_hidden = true;

		return $action;
	}

	public function show_header() {
		$action                   = clone $this;
		$action->is_header_hidden = false;

		return $action;
	}

	public function bulk() : self {
		$action          = clone $this;
		$action->is_bulk = true;

		return $action;
	}

	public function to_array() : array {
		$result = [
			'id'            => $this->id,
			'label'         => $this->label,
			'isPrimary'     => $this->icon !== '',
			'isDestructive' => $this->is_destructive,
			'icon'          => $this->icon,
			'callback'      => $this->callback(),
			'supportsBulk'  => $this->is_bulk,
		];

		if ( $this->type === self::TYPE_MODAL ) {
			$result['RenderModal']     = $this->render_modal();
			$result['hideModalHeader'] = $this->is_header_hidden;
		}

		return $result;
	}

	/**
	 * Function that returns the action callback.
	 * @since $ver$
	 * @return string|null The callback.
	 */
	private function callback() : ?string {
		if ( $this->type === self::TYPE_MODAL ) {
			return null;
		}

		try {
			$callback = sprintf(
				'( data ) => datakit_dataviews_actions.%s(data, %s)',
				'url',
				json_encode( $this->context(), JSON_THROW_ON_ERROR ),
			);
		} catch ( JsonException $e ) {
			return '';
		}

		return '__RAW__' . $callback . '__ENDRAW__';
	}

	private function render_modal() : ?string {
		if ( $this->type !== self::TYPE_MODAL ) {
			return null;
		}

		try {
			$modal = sprintf(
				'( props ) => %s({...props, context: %s})',
				'datakit_modal',
				json_encode( $this->context(), JSON_THROW_ON_ERROR ),
			);
		} catch ( JsonException $e ) {
			return '';
		}

		return '__RAW__' . $modal . '__ENDRAW__';
	}

	private function context() : array {
		return array_merge(
			$this->context,
			[
				'id'   => $this->id,
				'type' => $this->type,
			]
		);
	}
}