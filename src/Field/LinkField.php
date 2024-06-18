<?php

namespace DataKit\DataView\Field;

final class LinkField extends Field {
	private const TYPE_NONE = 'none';
	private const TYPE_FIELD = 'field';
	private const TYPE_URL = 'url';

	private string $type = self::TYPE_NONE;
	private string $link = '#';

	protected string $render = 'datakit_fields.link';

	public function linkToField( string $field_id ) : self {
		$clone       = clone $this;
		$clone->type = self::TYPE_FIELD;
		$clone->link = $field_id;

		return $clone;
	}

	public function linkToUrl( string $url ) : self {
		$clone       = clone $this;
		$clone->type = self::TYPE_URL;
		$clone->link = $url;

		return $clone;
	}
}
