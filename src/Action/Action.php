<?php

namespace DataKit\DataView\Action;

final class Action {
	private const TYPE_CALLBACK = 'callback';
	private const TYPE_MODAL = 'modal';

	private string $type;
	private string $id;
	private string $label;
	private bool $is_primary = false;
	private bool $is_destructive = false;
	private $callback = null;

	private function __construct() {
	}

	public static function modal() : self {
		$action       = new self;
		$action->type = self::TYPE_MODAL;

		return $action;
	}

	public static function callback( callable $callback ) : self {
		$action           = new self;
		$action->type     = self::TYPE_MODAL;
		$action->callback = $callback;

		return $action;
	}
}
