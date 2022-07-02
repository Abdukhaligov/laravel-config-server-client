<?php

namespace Abdukhaligov\LaravelConfigServerClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class ConfigServerProvider extends ServiceProvider {
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register() {
    //
  }

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot() {
    // https://github.com/vlucas/phpdotenv

    if (env('CONFIG_SERVER')) {
      $response = Http::get(env('CONFIG_SERVER'));

      self::setEnvironmentValue($response->json());
    }
  }

  // https://stackoverflow.com/a/54173207
  private static function setEnvironmentValue(array $values, $envFile = null) {
    $envFile = $envFile != null ? $envFile : app()->environmentFilePath();

    $str = file_get_contents($envFile);

    if (count($values) > 0) {
      $str .= "\n"; // In case the searched variable is in the last line without \n

      foreach ($values as $envKey => $envValue) {
        $keyPosition = strpos($str, "{$envKey}=");
        $endOfLinePosition = strpos($str, "\n", $keyPosition);
        $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

        // If key does not exist, add it
        if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
          $str .= "{$envKey}={$envValue}\n";
        } else {
          $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
        }
      }
    }

    $str = substr($str, 0, -1);
    if (!file_put_contents($envFile, $str)) return false;

    return true;
  }
}
