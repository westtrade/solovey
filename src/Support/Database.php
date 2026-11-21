<?php

declare(strict_types=1);

namespace Solovey\Support;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Locator\LocatorInterface;
use Cake\ORM\Locator\TableLocator;

/**
 * Подключение к SQLite и локатор таблиц CakePHP ORM.
 *
 * База — один файл `database/solovey.sqlite`. Соединение регистрируется лениво
 * и один раз: повторная регистрация перезаписала бы живое соединение, и
 * транзакция сидера потеряла бы изменения.
 *
 * `Configure::write('App.namespace')` нужен, чтобы локатор сам находил классы
 * `Solovey\Model\Table\ArticlesTable` по алиасу `Articles`: без этого Cake ищет
 * их в `App\Model\Table` и падает на «Table class not found».
 */
final class Database
{
	private static ?LocatorInterface $locator = null;

	public static function path(): string
	{
		return dirname(__DIR__, 2) . '/database/solovey.sqlite';
	}

	public static function locator(): LocatorInterface
	{
		if (self::$locator !== null) {
			return self::$locator;
		}

		$file = self::path();

		if (!is_file($file)) {
			throw AppError::make('database_schema_missing', ['file' => $file]);
		}

		Configure::write('App.namespace', 'Solovey');

		ConnectionManager::setConfig('default', [
			'className' => \Cake\Database\Connection::class,
			'driver' => \Cake\Database\Driver\Sqlite::class,
			'database' => $file,
			'encoding' => 'utf8',
			'cacheMetadata' => false,
		]);

		// В CakePHP 5 локатор берёт соединение из ConnectionManager::get('default')
		// сам: второй аргумент конструктора — фабрика запросов, а не имя соединения.
		self::$locator = new TableLocator();

		return self::$locator;
	}

	/**
	 * Короткая запись: `Database::table('Articles')`.
	 */
	public static function table(string $alias): \Cake\ORM\Table
	{
		return self::locator()->get($alias);
	}

	/**
	 * Сброс локатора: нужен сидеру, который создаёт схему до первого запроса.
	 */
	public static function reset(): void
	{
		self::$locator = null;
		ConnectionManager::drop('default');
	}
}
