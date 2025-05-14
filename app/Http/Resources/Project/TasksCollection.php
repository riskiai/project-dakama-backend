<?php

namespace App\Http\Resources\Project;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TasksCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $data = [];

        foreach ($this as $task) {
            $data[] = [
                'id'         => $task->id,
                'nama_task'  => $task->nama_task,
                'type'       => $this->typetask($task),
                'nominal'    => $task->nominal,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ];
        }

        return $data;
    }

    protected function typetask(Task $task)
    {
        if ($task->type == Task::JASA) {
            return 'Jasa';
        } elseif ($task->type == Task::MATERIAL) {
            return 'Material';
        } else {
            return 'Tidak Diketahui';
        }
    }

}
