<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $business_name
 * @property string $phone
 * @property string|null $email
 * @property string|null $product_name
 * @property string|null $message
 * @property bool $is_read
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'business_name', 'phone', 'email', 'product_name', 'message', 'is_read'])]
class ContactEnquiry extends Model
{
    use LogsModelActivity;

    protected string $activityLogName = 'enquiries';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }
}
