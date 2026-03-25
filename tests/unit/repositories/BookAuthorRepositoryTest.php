<?php

namespace tests\unit\repositories;

use app\repositories\BookAuthorRepository;

class BookAuthorRepositoryTest extends \Codeception\Test\Unit
{
    public function testNormalizeAuthorIdsRemovesInvalidValuesAndDuplicates(): void
    {
        $repository = new BookAuthorRepository();

        $result = $repository->normalizeAuthorIds(['4', 2, '0', -1, '4', '7', 'foo', 2]);

        verify($result)->equals([2, 4, 7]);
    }
}
