<?php

namespace Abdukhaligov\LaravelConfigServerClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use function Symfony\Component\Translation\t;

class ConfigServerProvider extends ServiceProvider
{
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    //
  }

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    $this->registerCommands();

    if (ConfigClient::$runEveryTime) {
      self::updateEnvConfigs(false);
    }
  }


  /**
   * Register the package's commands.
   *
   * @return void
   */
  protected function registerCommands()
  {
    if ($this->app->runningInConsole()) {
      $this->commands([
        Console\UpdateCommand::class,
      ]);
    }
  }

  /**
   * @param bool $dryRun
   * @return void
   * @link https://github.com/vlucas/phpdotenv
   */
  public static function updateEnvConfigs(bool $dryRun)
  {
    if (env('CONFIG_SERVER')) {
      $response = Http::get(env('CONFIG_SERVER'));
      if ($response->ok()) {
        self::setEnvironmentValue($response->json(), $dryRun);
      }
    }
  }

  /**
   * @param array $values
   * @param $envFile
   * @return bool
   * @link https://stackoverflow.com/a/54173207
   */
  private static function setEnvironmentValue(array $values, $dryRun): bool
  {
    $envFile = ConfigClient::$customEnvFile ?? app()->environmentFilePath();
    ConfigClient::$newConfigCount = 0;
    ConfigClient::$updatedConfigCount = 0;

    $str = file_get_contents($envFile);

    if (count($values) > 0) {
      foreach ($values as $envKey => $envValue) {
        $keyPosition = strpos($str, "{$envKey}=");
        $endOfLinePosition = strpos($str, "\n", $keyPosition);
        $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

        // If key does not exist, add it
        if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
          $str .= "{$envKey}={$envValue}\n";
          ConfigClient::$newConfigCount++;
        } else if (($newLine = "{$envKey}={$envValue}") && $oldLine != $newLine) {
          $str = str_replace($oldLine, $newLine, $str);
          ConfigClient::$updatedConfigCount++;
        }
      }

      $str .= "\n"; // In case the searched variable is in the last line without \n
    }

    $str = substr($str, 0, -1);

    return $dryRun || file_put_contents($envFile, $str) !== false;
  }
}
