<?php

namespace App\Http\Controllers\Project;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Facades\MessageDakama;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\Project\TasksCollection;
use App\Http\Requests\Project\Task\CreateRequest;
use App\Http\Requests\Project\Task\UpdateRequest;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::query();

        // Optional: tambahkan filter jika ada request type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $tasks = $query->paginate($request->per_page);

        return new TasksCollection($tasks);
    }

    public function indexall(Request $request) {
        $query = Task::query();

        // Optional: tambahkan filter jika ada request type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $tasks = $query->get();

        return new TasksCollection($tasks);
    }

    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $task = Task::create([
                'nama_task' => $request->nama_task,
                'type'      => $request->type,
                'nominal'   => $request->nominal,
            ]);

            DB::commit();
            return MessageDakama::success("Task '{$task->nama_task}' berhasil dibuat.");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal membuat task: " . $th->getMessage());
        }
    }

    public function show(string $id)
    {
        $task = Task::find($id);
        if (!$task) {
            return MessageDakama::notFound('data not found!');
        }

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' => [
               'id'         => $task->id,
                'nama_task'  => $task->nama_task,
                'type'       => $this->typetask($task),
                'nominal'    => $task->nominal,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ]
        ]);
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

    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        $task = Task::find($id);
        if (!$task) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            $task->update($request->all());

            DB::commit();
            return MessageDakama::success("task $task->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        $task = Task::find($id);
        if (!$task) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            $task->delete();

            DB::commit();
            return MessageDakama::success("task $task->name has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
