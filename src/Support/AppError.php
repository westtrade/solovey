<?php

declare(strict_types=1);

namespace Solovey\Support;

/**
 * Ошибка приложения с символьным кодом.
 *
 * Код — snake_case из реестра `errors.php`. Сообщение человеку, `details` —
 * машиночитаемые подробности (id записи, имя поля), они уходят в лог и в
 * JSON-конверт `{"error":{"code":…,"message":…,"details":…}}`.
 */
final class AppError extends \RuntimeException
{
	/**
	 * @param array<string, mixed> $details
	 */
	public function __construct(
		public readonly string $errorCode,
		string $message,
		public readonly array $details = [],
		public readonly int $status = 500,
		?\Throwable $previous = null,
	) {
		parent::__construct($message, 0, $previous);
	}

	/**
	 * @param array<string, mixed> $details
	 */
	public static function make(string $code, array $details = [], ?\Throwable $previous = null): self
	{
		$definition = ErrorCodes::get($code);

		if ($definition === null) {
			// Незарегистрированный код — это дефект кода, а не данных: падаем
			// громко, иначе опечатка уедет в прод и превратится в 500 без следа.
			throw new \LogicException(sprintf('Unknown error code "%s"', $code));
		}

		return new self(
			$code,
			$definition['message'],
			$details,
			$definition['status'],
			$previous,
		);
	}

	/**
	 * @return array{code: string, message: string, details: array<string, mixed>}
	 */
	public function toArray(): array
	{
		return [
			'code' => $this->errorCode,
			'message' => $this->getMessage(),
			'details' => $this->details,
		];
	}
}
