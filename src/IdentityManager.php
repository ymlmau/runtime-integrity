<?php
namespace YmlMau\RuntimeIntegrity;

use YmlMau\RuntimeIntegrity\Support\Uuid;

final class IdentityManager
{
    public function ensure(array $state)
    {
        if (!isset($state['identity']) || !is_array($state['identity'])) {
            $state['identity'] = [];
        }
        if (empty($state['identity']['installation_id'])) {
            $state['identity']['installation_id'] = Uuid::v4();
            $state['identity']['created_at'] = gmdate('c');
        }
        return $state;
    }
}
