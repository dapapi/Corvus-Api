<?php

namespace App\Http\Transformers;

use App\Models\ContractArchive;
use League\Fractal\TransformerAbstract;

class ContractArchiveTransformer extends TransformerAbstract
{
    public function transform(ContractArchive $archive)
    {
        return [
            'file_name' => $archive->file_name,
            'size' => $archive->size,
            'url' => $archive->archive,
        ];
    }
}