<?php

namespace DataKit\DataViews\DataView;

use JsonException;

/**
 * Represent an action object on a DataView.
 *
 * Actions are rendered as buttons with an icon.
 *
 * @link  https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#actions-object
 *
 * @since $ver$
 */
final class Action {
	/**
	 * The possible action types.
	 *
	 * @since $ver$
	 */
	private const TYPE_URL   = 'url';
	private const TYPE_AJAX  = 'ajax';
	private const TYPE_MODAL = 'modal';

	/**
	 * The action type.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * The unique ID for the action.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * The action button label.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $label;

	/**
	 * The icon name used for a primary action.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $icon = '';

	/**
	 * Whether the action is destructive and is displayed as such.
	 *
	 * @since $ver$
	 *
	 * @var bool
	 */
	private bool $is_destructive = false;

	/**
	 * Whether the header of a modal should be hidden.
	 *
	 * @since $ver$
	 *
	 * @var bool
	 */
	private bool $is_header_hidden = false;

	/**
	 * Whether the action is applicable to multiple items.
	 *
	 * @since $ver$
	 *
	 * @var bool
	 */
	private bool $is_bulk = false;

	/**
	 * Object that holds context information for the desired action.
	 *
	 * This context is available during the rendering of the action.
	 *
	 * @since $ver$
	 *
	 * @var array
	 */
	private array $context = [];

	/**
	 * Field IDs that are required to be present for the action to be eligible.
	 *
	 * @since $ver$
	 *
	 * @var string[]
	 */
	private array $required = [];

	/**
	 * Creates an action.
	 *
	 * Note: the constructor is private as there are multiple named constructors available.
	 *
	 * @since $ver$
	 *
	 * @see   Action::modal() for an action that opens a modal.
	 * @see   Action::url() for an action that opens a URL.
	 * @see   Action::ajax() for an action that is performed via an Ajax request.
	 *
	 * @param string $id    The unique ID of the action.
	 * @param string $label The label of the action.
	 */
	private function __construct( string $id, string $label ) {
		$this->id    = $id;
		$this->label = $label;
	}

	/**
	 * Creates an action that opens a URL inside a modal.
	 *
	 * @since $ver$
	 *
	 * @param string $id               The unique ID of the action.
	 * @param string $label            The label of the action.
	 * @param string $url              The URL to open inside the modal.
	 * @param bool   $is_header_hidden Whether the header is hidden on the modal.
	 *
	 * @return self The action instance.
	 */
	public static function modal( string $id, string $label, string $url, bool $is_header_hidden = false ): self {
		$action                   = new self( $id, $label );
		$action->type             = self::TYPE_MODAL;
		$action->is_header_hidden = $is_header_hidden;

		$action->context['url'] = $url;

		return $action;
	}

	/**
	 * Creates an action that opens a URL in a browser window.
	 *
	 * @since $ver$
	 *
	 * @param string $id             The unique ID of the action.
	 * @param string $label          The label of the action.
	 * @param string $url            The URL to open.
	 * @param bool   $in_same_window Whether to op the url in the same window.
	 *
	 * @return self The action instance.
	 */
	public static function url( string $id, string $label, string $url, bool $in_same_window = false ): self {
		$action       = new self( $id, $label );
		$action->type = self::TYPE_URL;

		$action->context['url']            = $url;
		$action->context['use_new_window'] = ! $in_same_window;

		return $action;
	}

	/**
	 * Creates an action that calls a URL via an Ajax call.
	 *
	 * @since $ver$
	 *
	 * @param string $id                 The unique ID of the action.
	 * @param string $label              The label of the action.
	 * @param string $url                The URL to open inside the modal.
	 * @param string $method             The method type to use for the Ajax call (GET, POST, PUT, etc.).
	 * @param array  $params             The parameters passed along to the URL.
	 * @param bool   $use_single_request Whether there should be a single ajax call on a BULK action. Will perform a
	 *                                   call per item when set to `false`.
	 *
	 * @return self The action instance.
	 */
	public static function ajax(
		string $id,
		string $label,
		string $url,
		string $method = 'GET',
		array $params = [],
		bool $use_single_request = false
	): self {
		$action       = new self( $id, $label );
		$action->type = self::TYPE_AJAX;

		$action->context['url']                = $url;
		$action->context['method']             = $method;
		$action->context['params']             = $params;
		$action->context['use_single_request'] = $use_single_request;

		return $action;
	}

	/**
	 * Adds a confirmation message before triggering the action.
	 *
	 * @since $ver$
	 *
	 * @param string|null $message The confirmation message.
	 *
	 * @return self The action instance.
	 */
	public function confirm( ?string $message ): self {
		$action                     = clone $this;
		$action->context['confirm'] = $message;

		return $action;
	}

	/**
	 * Returns an action that is marked as primary.
	 *
	 * Note: A primary action requires an icon. DataViews currently uses Dashicons only.
	 *
	 * @link  https://developer.wordpress.org/resource/dashicons/#podio
	 *
	 * @since $ver$
	 *
	 * @param string $icon The icon to show.
	 *
	 * @return self The action instance.
	 */
	public function primary( string $icon ): self {
		$action       = clone $this;
		$action->icon = $icon;

		return $action;
	}

	/**
	 * Returns an action that is marked as secondary.
	 *
	 * @since $ver$
	 *
	 * @return self The action instance.
	 */
	public function secondary(): self {
		$action       = clone $this;
		$action->icon = '';

		return $action;
	}

	/**
	 * Returns an action that is marked as destructive.
	 *
	 * @since $ver$
	 *
	 * @return self The action instance.
	 */
	public function destructive(): self {
		$action                 = clone $this;
		$action->is_destructive = true;

		return $action;
	}

	/**
	 * Returns an action that is marked as *not* destructive.
	 *
	 * @since $ver$
	 *
	 * @return self The action instance.
	 */
	public function not_destructive(): self {
		$action                 = clone $this;
		$action->is_destructive = false;

		return $action;
	}

	/**
	 * Returns an action that can be performed on multiple items at once.
	 *
	 * @since $ver$
	 *
	 * @return self The action instance.
	 */
	public function bulk(): self {
		$action          = clone $this;
		$action->is_bulk = true;

		return $action;
	}

	/**
	 * Returns an instance that allows scripts to be executed.
	 *
	 * @since $ver$
	 * @return self The action.
	 */
	public function allow_scripts(): self {
		$clone                                = clone $this;
		$clone->context['is_scripts_allowed'] = true;

		return $clone;
	}

	/**
	 * Returns an instance that removes scripts from the content.
	 *
	 * @since $ver$
	 * @return self The action.
	 */
	public function deny_scripts(): self {
		$clone                                = clone $this;
		$clone->context['is_scripts_allowed'] = false;

		return $clone;
	}

	/**
	 * Returns an action that can be performed one item at a time.
	 *
	 * @since $ver$
	 *
	 * @return self The action instance.
	 */
	public function single(): self {
		$action          = clone $this;
		$action->is_bulk = false;

		return $action;
	}

	/**
	 * Returns an action that can requires certain values to be present.
	 *
	 * @since $ver$
	 *
	 * @return self The action instance.
	 */
	public function requires( array $required_ids ): self {
		$action = clone $this;

		$action->required = array_filter( $required_ids, 'is_string' );

		return $action;
	}

	/**
	 * Returns a serialized state of the action.
	 *
	 * @since $ver$
	 *
	 * @return array The serialized state.
	 */
	public function to_array(): array {
		$result = [
			'id'            => $this->id,
			'label'         => $this->label,
			'isPrimary'     => '' !== $this->icon,
			'isDestructive' => $this->is_destructive,
			'isEligible'    => $this->js_eligible(),
			'icon'          => $this->icon,
			'callback'      => $this->js_callback(),
			'supportsBulk'  => $this->is_bulk,
		];

		if ( self::TYPE_MODAL === $this->type ) {
			$result['RenderModal']     = $this->js_render_modal();
			$result['hideModalHeader'] = $this->is_header_hidden;
		}

		return $result;
	}

	/**
	 * Returns the actions JavaScript callback.
	 *
	 * The URL and Ajax action are both handled through a `url` JavaScript callback.
	 *
	 * Note: __RAW__ and __ENDRAW__ is used to make sure the callback is rendered as JavaScript, instead of a string.
	 *
	 * @since $ver$
	 *
	 * @return string|null The JavaScript callback.
	 */
	private function js_callback(): ?string {
		if ( self::TYPE_MODAL === $this->type ) {
			return null;
		}

		try {
			$callback = sprintf(
				'( data, { registry } ) => datakit_dataviews_actions.%s(data, {registry, ... %s})',
				'url',
				json_encode( $this->context(), JSON_THROW_ON_ERROR ),
			);
		} catch ( JsonException $e ) {
			return '';
		}

		return '__RAW__' . $callback . '__ENDRAW__';
	}

	/**
	 * Returns the JavaScript `isEligible` callback for this action.
	 *
	 * @since $ver$
	 *
	 * @return string|null The JavaScript callback.
	 */
	private function js_eligible(): ?string {
		if ( [] === $this->required ) {
			return null;
		}

		try {
			$callback = sprintf(
				'item => %s.every( (key) => null !== (item[key] || null) )',
				json_encode( $this->required, JSON_THROW_ON_ERROR ),
			);
		} catch ( JsonException $e ) {
			return '';
		}

		return '__RAW__' . $callback . '__ENDRAW__';
	}

	/**
	 * Returns the actions JavaScript modal properties.
	 *
	 * @since $ver$
	 *
	 * @return string|null The JavaScript settings.
	 */
	private function js_render_modal(): ?string {
		if ( self::TYPE_MODAL !== $this->type ) {
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

	/**
	 * Returns the context required for the different action types.
	 *
	 * @since $ver$
	 *
	 * @return array The context object.
	 */
	private function context(): array {
		return array_merge(
			$this->context,
			[
				'id'   => $this->id,
				'type' => $this->type,
			],
		);
	}
}
