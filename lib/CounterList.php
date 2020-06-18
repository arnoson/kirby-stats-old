<?php

namespace KirbyStats;

/**
 * A list of counters that can be stored in a string.
 */
class CounterList {
  /**
   * @var array
   */
  protected $data;

  /**
   * Create a new CounterList object.
   * 
   * @param string|null $string
   */
  public function __construct(string $string = null) {
    $this->data = $string ? $this->decode($string) : [];
  }

  /**
   * Increase counter.
   * 
   * @param string $name - The name of the counter.
   * @param int $amount = 1 - The amount by which to increase the counter.
   * @return CounterList
   */
  public function increment(string $name, $amount = 1) {
    $this->data[$name] = $this->data[$name] ?? 0;
    $this->data[$name] += $amount;
    return $this;
  }

  /**
   * Decrease counter.
   * 
   * @param string $name - The name of the counter.
   * @param int $amount = 1 - The amount by which to decrease the counter.
   * @return CounterList
   */
  public function decrement(string $name, $amount = 1) {
    $this->increment($name, $amount * -1);
    return $this;
  }

  /**
   * Set counters from string.
   * 
   * @param string
   * @return CounterList
   */
  public function fromString(string $string) {
    $this->data = $this->decode($string);
    return $this;
  }

  /**
   * Convert counters to string.
   * 
   * @return string
   */
  public function toString(): string {
    return $this->encode($this->data);
  }

  /**
   * Convert counters to array.
   * 
   * @return array
   */  
  public function toArray(): array {
    return $this->data;
  }

  /**
   * Decode string.
   * 
   * @param string $string
   * @return array
   */
  protected function decode(string $string): array {
    $data = [];
    $pairs = explode(' ', $string);
    foreach ($pairs as $pair) {
      [$name, $count] = explode('=', $pair);
      $data[$name] = (int)$count;
    }
    return $data;
  }

  /**
   * Encode data.
   * 
   * @param array $data
   * @return string
   */
  protected function encode(array $data): string {
    $encodedPairs = [];
    foreach ($data as $name => $count) {
      array_push($encodedPairs, $name . '=' . $count);
    }

    return implode(' ', $encodedPairs);
  }
}