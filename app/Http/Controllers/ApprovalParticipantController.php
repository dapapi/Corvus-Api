<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovalFlow\StoreFixedParticipantRequest;
use App\Http\Transformers\ApprovalParticipantTransformer;
use App\Models\ApprovalFlow\FixedParticipant;
use App\Models\ApprovalForm\ApprovalForm;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalParticipantController extends Controller
{
    public function store(StoreFixedParticipantRequest $request, ApprovalForm $approval)
    {
        $participants = $request->get('participants');
        DB::beginTransaction();
        try {
            foreach ($participants as $participant) {
                FixedParticipant::create([
                    'form_id' => $approval->form_id,
                    'notice_id' => $participant['id'],
                    'notice_type' => $participant['type'],
                ]);
            }
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('存储知会人失败');
        }
        DB::commit();
        return $this->response->created();
    }

    public function getFixedParticipants(Request $request, ApprovalForm $approval)
    {
        $formId = $approval->form_id;
        $participants = FixedParticipant::where('form_id', $formId)->get();

        return $this->response->collection($participants, new ApprovalParticipantTransformer());
    }

    // todo 是否存一遍固定知会人
    // todo 详情页中的获取。写在detail中
}
