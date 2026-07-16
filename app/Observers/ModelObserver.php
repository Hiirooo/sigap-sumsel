<?php

namespace App\Observers;

use App\Models\LogAktivitas;
use Illuminate\Database\Eloquent\Model;

class ModelObserver
{
    protected function logActivity(Model $model, string $action)
    {
        LogAktivitas::create([
            'user_id' => auth()->id() ?? null,
            'aksi' => $action,
            'model' => class_basename($model),
            'detail' => json_encode($model->getAttributes())
        ]);
    }

    public function created(Model $model): void
    {
        $this->logActivity($model, 'CREATE');
    }

    public function updated(Model $model): void
    {
        $this->logActivity($model, 'UPDATE');
    }

    public function deleted(Model $model): void
    {
        $this->logActivity($model, 'DELETE');
    }
}
