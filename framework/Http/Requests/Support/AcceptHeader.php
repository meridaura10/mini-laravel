<?php

namespace Framework\Kernel\Http\Requests\Support;

class AcceptHeader
{
    protected array $items = [];

    protected bool $sorted = false;

    public function __construct(array $items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(AcceptHeaderItem $item): static
    {
        $this->items[$item->getValue()] = $item;
        $this->sorted = false;

        return $this;
    }

    public static function fromString(?string $headerValue): self
    {
        $parts = HeaderUtils::split($headerValue ?? '', ',;=');

        return new self(array_map(function ($subParts) {
            static $index = 0;
            $part = array_shift($subParts);
            $attributes = HeaderUtils::combine($subParts);

            $item = new AcceptHeaderItem($part[0], $attributes);
            $item->setIndex($index++);

            return $item;
        }, $parts));
    }

    public function all(): array
    {
        $this->sort();

        return $this->items;
    }

    private function sort(): void
    {
        if (!$this->sorted) {
            uasort($this->items, function (AcceptHeaderItem $a, AcceptHeaderItem $b) {
                $qA = $a->getQuality();
                $qB = $b->getQuality();

                if ($qA === $qB) {
                    return $a->getIndex() > $b->getIndex() ? 1 : -1;
                }

                return $qA > $qB ? -1 : 1;
            });

            $this->sorted = true;
        }
    }
}