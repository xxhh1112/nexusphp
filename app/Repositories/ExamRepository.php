<?php
namespace App\Repositories;

use App\Exceptions\NexusException;
use App\Models\Exam;
use App\Models\ExamProgress;
use App\Models\ExamUser;
use App\Models\Message;
use App\Models\Setting;
use App\Models\Torrent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ExamRepository extends BaseRepository
{
    public function getList(array $params)
    {
        $query = Exam::query();
        list($sortField, $sortType) = $this->getSortFieldAndType($params);
        $query->orderBy($sortField, $sortType);
        return $query->paginate();
    }

    public function store(array $params)
    {
        $this->checkIndexes($params);
        $valid = $this->listValid();
        if ($valid->isNotEmpty() && $params['status'] == Exam::STATUS_ENABLED) {
            throw new NexusException("Valid exam already exists.");
        }
        $exam = Exam::query()->create($params);
        return $exam;
    }

    public function update(array $params, $id)
    {
        $this->checkIndexes($params);
        $valid = $this->listValid($id);
        if ($valid->isNotEmpty() && $params['status'] == Exam::STATUS_ENABLED) {
            throw new NexusException("Valid exam already exists.");
        }
        $exam = Exam::query()->findOrFail($id);
        $exam->update($params);
        return $exam;
    }

    private function checkIndexes(array $params)
    {
        if (empty($params['indexes'])) {
            throw new \InvalidArgumentException("Require index.");
        }
        $validIndex = array_filter($params['indexes'], function ($value) {
            return isset($value['checked']) && $value['checked']
                && isset($value['require_value']) && $value['require_value'] > 0;
        });
        if (empty($validIndex)) {
            throw new \InvalidArgumentException("Require valid index.");
        }
        return true;
    }

    public function getDetail($id)
    {
        $exam = Exam::query()->findOrFail($id);
        return $exam;
    }

    public function delete($id)
    {
        $exam = Exam::query()->findOrFail($id);
        $result = $exam->delete();
        return $result;
    }

    public function listIndexes()
    {
        $out = [];
        foreach(Exam::$indexes as $key => $value) {
            $value['index'] = $key;
            $out[] = $value;
        }
        return $out;
    }

    /**
     * list valid exams
     *
     * @param null $excludeId
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function listValid($excludeId = null)
    {
        $now = Carbon::now();
        $query = Exam::query()
            ->where('begin', '<=', $now)
            ->where('end', '>=', $now)
            ->where('status', Exam::STATUS_ENABLED)
            ->orderBy('id', 'desc');
        if ($excludeId) {
            $query->whereNotIn('id', Arr::wrap($excludeId));
        }
        return $query->get();
    }

    /**
     * list user match exams
     *
     * @param $uid
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function listMatchExam($uid)
    {
        $logPrefix = "uid: $uid";
        $exams = $this->listValid();
        if ($exams->isEmpty()) {
            do_log("$logPrefix, no valid exam.");
            return $exams;
        }

        $matched = $exams->filter(function (Exam $exam) use ($uid, $logPrefix) {
            return $this->isExamMatchUser($exam, $uid);
        });

        return $matched;
    }

    private function isExamMatchUser(Exam $exam, $user)
    {
        if (!$user instanceof User) {
            $user = User::query()->findOrFail(intval($user), ['id', 'username', 'added', 'class']);
        }
        $logPrefix = sprintf('exam: %s, user: %s', $exam->id, $user->id);
        $filters = $exam->filters;
        if (empty($filters->classes)) {
            do_log("$logPrefix, exam: {$exam->id} no class");
            return false;
        }
        if (!in_array($user->class, $filters->classes)) {
            do_log("$logPrefix, user class: {$user->class} not in: " . json_encode($filters));
            return false;
        }
        if (!$user->added) {
            do_log("$logPrefix, user no added time", 'warning');
            return false;
        }

        $added = $user->added->toDateTimeString();
        $registerTimeBegin = isset($filters->register_time_range[0]) ? Carbon::parse($filters->register_time_range[0])->toDateString() : '';
        $registerTimeEnd = isset($filters->register_time_range[1]) ? Carbon::parse($filters->register_time_range[1])->toDateString() : '';
        if (empty($registerTimeBegin)) {
            do_log("$logPrefix, exam: {$exam->id} no register_time_begin");
            return false;
        }
        if ($added < $registerTimeBegin) {
            do_log("$logPrefix, user added: $added not after: " . $registerTimeBegin);
            return false;
        }

        if (empty($registerTimeEnd)) {
            do_log("$logPrefix, exam: {$exam->id} no register_time_end");
            return false;
        }
        if ($added > $registerTimeEnd) {
            do_log("$logPrefix, user added: $added not before: " . $registerTimeEnd);
            return false;
        }
        return true;
    }


    /**
     * assign exam to user
     *
     * @param int $uid
     * @param null $examId
     * @param null $begin
     * @param null $end
     * @return mixed
     */
    public function assignToUser(int $uid, $examId = null, $begin = null, $end = null)
    {
        $logPrefix = "uid: $uid, examId: $examId, begin: $begin, end: $end";
        if ($examId > 0) {
            $exam = Exam::query()->find($examId);
        } else {
            $exams = $this->listValid();
            if ($exams->isEmpty()) {
                throw new NexusException("No valid exam.");
            }
            if ($exams->count() > 1) {
                do_log(last_query());
                throw new NexusException("valid exam more than 1.");
            }
            $exam = $exams->first();
        }
        $user = User::query()->findOrFail($uid);
        if (!$this->isExamMatchUser($exam, $user)) {
            throw new NexusException("Exam: {$exam->id} no match this user.");
        }
        if ($user->exams()->where('status', ExamUser::STATUS_NORMAL)->exists()) {
            throw new NexusException("User: $uid already has exam on the way.");
        }
        $exists = $user->exams()->where('exam_id', $exam->id)->exists();
        if ($exists) {
            throw new NexusException("Exam: {$exam->id} already assign to user: {$user->id}.");
        }
        $data = [
            'exam_id' => $exam->id,
        ];
        if ($begin && $end) {
            $logPrefix .= ", specific begin and end";
            $data['begin'] = $begin;
            $data['end'] = $end;
        }
        do_log("$logPrefix, data: " . nexus_json_encode($data));
        $result = $user->exams()->create($data);
        return $result;
    }

    public function listUser(array $params)
    {
        $query = ExamUser::query();
        if (!empty($params['uid'])) {
            $query->where('uid', $params['uid']);
        }
        if (!empty($params['exam_id'])) {
            $query->where('exam_id', $params['exam_id']);
        }
        list($sortField, $sortType) = $this->getSortFieldAndType($params);
        $query->orderBy($sortField, $sortType);
        $result = $query->with(['user', 'exam'])->paginate();
        return $result;

    }

    public function addProgress(int $uid, int $torrentId, array $indexAndValue)
    {
        $logPrefix = "uid: $uid, torrentId: $torrentId, indexAndValue: " . json_encode($indexAndValue);
        do_log($logPrefix);

        $user = User::query()->findOrFail($uid);
        $user->checkIsNormal();

        $now = Carbon::now()->toDateTimeString();
        $examUser = $user->exams()->where('status', ExamUser::STATUS_NORMAL)->orderBy('id', 'desc')->first();
        if (!$examUser) {
            do_log("no exam is on the way, " . last_query());
            return false;
        }
        $exam = $examUser->exam;
        if (!$exam) {
            throw new NexusException("exam: {$examUser->exam_id} not exists.");
        }
        $begin = $examUser->begin ?? $exam->begin;
        $end = $examUser->end ?? $exam->end;
        if ($now < $begin || $now > $end) {
            do_log(sprintf("now: %s, not in exam time range: %s ~ %s", $now, $begin, $end));
            return false;
        }
        $indexes = collect($exam->indexes)->keyBy('index');
        do_log("examUser: " . $examUser->toJson() . ", indexes: " . $indexes->toJson());

        $torrentFields = ['id', 'visible', 'banned'];
        $torrent = Torrent::query()->findOrFail($torrentId, $torrentFields);
        $torrent->checkIsNormal($torrentFields);

        $insert = [];
        foreach ($indexAndValue as $indexId => $value) {
            if (!$indexes->has($indexId)) {
                do_log(sprintf('Exam: %s does not has index: %s.', $exam->id, $indexId));
                continue;
            }
            $indexInfo = $indexes->get($indexId);
            if (!isset($indexInfo['checked']) || !$indexInfo['checked']) {
                do_log(sprintf('Exam: %s index: %s is not checked.', $exam->id, $indexId));
                continue;
            }
            $insert[] = [
                'exam_user_id' => $examUser->id,
                'uid' => $user->id,
                'exam_id' => $exam->id,
                'torrent_id' => $torrentId,
                'index' => $indexId,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (empty($insert)) {
            do_log("no progress to insert.");
            return false;
        }
        ExamProgress::query()->insert($insert);
        do_log("[addProgress] " . nexus_json_encode($insert));

        $examProgress = $this->calculateProgress($examUser);
        $examProgressFormatted = $this->getProgressFormatted($exam, $examProgress);
        $examNotPassed = array_filter($examProgressFormatted, function ($item) {
            return !$item['passed'];
        });
        $update = [
            'progress' => $examProgress,
            'is_done' => count($examNotPassed) ? ExamUser::IS_DONE_NO : ExamUser::IS_DONE_YES,
        ];
        do_log("[updateProgress] " . nexus_json_encode($update));
        $examUser->update($update);
        return true;
    }

    public function getUserExamProgress($uid, $status = null, $with = ['exam', 'user'])
    {
        $logPrefix = "uid: $uid";
        $query = ExamUser::query()->where('uid', $uid)->orderBy('exam_id', 'desc');
        if (!is_null($status)) {
            $query->where('status', $status);
        }
        if (!empty($with)) {
            $query->with($with);
        }
        $examUsers = $query->get();
        if ($examUsers->isEmpty()) {
            return null;
        }
        if ($examUsers->count() > 1) {
            do_log("$logPrefix, user exam more than 1.", 'warning');
        }
        $examUser = $examUsers->first();
        $exam = $examUser->exam;
        if (empty($examUser->begin) || empty($examUser->end)) {
            $examUser->begin = $exam->begin;
            $examUser->end = $exam->end;
        }
        $progress = $this->calculateProgress($examUser);
        do_log("$logPrefix, progress: " . nexus_json_encode($progress));
        $examUser->progress = $progress;
        $examUser->progress_formatted = $this->getProgressFormatted($exam, $progress);
        return $examUser;
    }

    private function calculateProgress(ExamUser $examUser)
    {
        $exam = $examUser->exam;
        $logPrefix = "examUser: " . $examUser->id;
        if ($examUser->begin) {
            $logPrefix .= ", begin from examUser: " . $examUser->id;
            $begin = $examUser->begin;
        } elseif ($exam->begin) {
            $logPrefix .= ", begin from exam: " . $exam->id;
            $begin = $exam->begin;
        } else {
            do_log("$logPrefix, no begin");
            return null;
        }
        if ($examUser->end) {
            $logPrefix .= ", end from examUser: " . $examUser->id;
            $end = $examUser->end;
        } elseif ($exam->end) {
            $logPrefix .= ", end from exam: " . $exam->id;
            $end = $exam->end;
        } else {
            do_log("$logPrefix, no end");
            return null;
        }
        $progressSum = $examUser->progresses()
            ->where('created_at', '>=', $begin)
            ->where('created_at', '<=', $end)
            ->selectRaw("`index`, sum(`value`) as sum")
            ->groupBy(['index'])
            ->get()
            ->pluck('sum', 'index')
            ->toArray();
        $logPrefix .= ", progressSum raw: " . json_encode($progressSum) . ", query: " . last_query();

        $index = Exam::INDEX_SEED_TIME_AVERAGE;
        if (isset($progressSum[$index])) {
            $torrentCount = $examUser->progresses()
                ->where('index', $index)
                ->selectRaw('count(distinct(torrent_id)) as torrent_count')
                ->first()
                ->torrent_count;
            $progressSum[$index] = intval($progressSum[$index] / $torrentCount);
            $logPrefix .= ", get torrent count: $torrentCount, from query: " . last_query();
        }

        do_log("$logPrefix, final progressSum: " . json_encode($progressSum));

        return $progressSum;

    }

    private function getProgressFormatted(Exam $exam, array $progress, $locale = null)
    {
        $result = [];
        foreach ($exam->indexes as $key => $index) {
            if (!isset($index['checked']) || !$index['checked']) {
                continue;
            }
            $currentValue = $progress[$index['index']] ?? 0;
            $requireValue = $index['require_value'];
            switch ($index['index']) {
                case Exam::INDEX_UPLOADED:
                case Exam::INDEX_DOWNLOADED:
                    $currentValueFormatted = mksize($currentValue);
                    $requireValueAtomic = $requireValue * 1024 * 1024 * 1024;
                    break;
                case Exam::INDEX_SEED_TIME_AVERAGE:
                    $currentValueFormatted = number_format($currentValue / 3600, 2) . " {$index['unit']}";
                    $requireValueAtomic = $requireValue * 3600;
                    break;
                default:
                    $currentValueFormatted = $currentValue;
                    $requireValueAtomic = $requireValue;
            }
            $index['index_formatted'] = nexus_trans('exam.index_text_' . $index['index']);
            $index['require_value_formatted'] = "$requireValue " . ($index['unit'] ?? '');
            $index['current_value'] = $currentValue;
            $index['current_value_formatted'] = $currentValueFormatted;
            $index['passed'] = $currentValue >= $requireValueAtomic;
            $result[] = $index;
        }
        return $result;
    }


    public function removeExamUser(int $examUserId)
    {
        $examUser = ExamUser::query()->findOrFail($examUserId);
        $result = DB::transaction(function () use ($examUser) {
            $examUser->progresses()->delete();
            return $examUser->delete();
        });
        return $result;
    }

    public function cronjonAssign()
    {
        $exams = $this->listValid();
        if ($exams->isEmpty()) {
            do_log("No valid exam.");
            return false;
        }
        if ($exams->count() > 1) {
            do_log("Valid exam more than 1.", "warning");
        }
        /** @var Exam $exam */
        $exam = $exams->first();
        $userTable = (new User())->getTable();
        $examUserTable = (new ExamUser())->getTable();
        $baseQuery = User::query()
            ->leftJoin($examUserTable, function (JoinClause $join) use ($examUserTable, $userTable) {
                $join->on("$userTable.id", "=", "$examUserTable.uid")
                    ->on("$examUserTable.status", "=", DB::raw(ExamUser::STATUS_NORMAL));
            })
            ->whereRaw("$examUserTable.id is null")
            ->selectRaw("$userTable.*")
            ->orderBy("$userTable.id", "asc");
        $size = 100;
        $minId = 0;
        $result = 0;
        while (true) {
            $logPrefix = sprintf('[%s], exam: %s, size: %s', __FUNCTION__, $exam->id , $size);
            $users = (clone $baseQuery)->where("$userTable.id", ">", $minId)->limit($size)->get();
            if ($users->isEmpty()) {
                do_log("$logPrefix, no more data..." . last_query());
                break;
            }
            $insert = [];
            $now = Carbon::now()->toDateTimeString();
            foreach ($users as $user) {
                $minId = $user->id;
                $currentLogPrefix = sprintf("$logPrefix, user: %s", $user->id);
                if (!$this->isExamMatchUser($exam, $user)) {
                    do_log("$currentLogPrefix, exam not match this user.");
                    continue;
                }
                $insert[] = [
                    'uid' => $user->id,
                    'exam_id' => $exam->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                do_log("$currentLogPrefix, exam will be assigned to this user.");
            }
            if (!empty($insert)) {
                $result += count($insert);
                ExamUser::query()->insert($insert);
            }
        }
        return $result;
    }

    public function cronjobCheckout($ignoreTimeRange = false)
    {
        $now = Carbon::now()->toDateTimeString();
        $examUserTable = (new ExamUser())->getTable();
        $examTable = (new Exam())->getTable();
        $baseQuery = ExamUser::query()
            ->join($examTable, "$examUserTable.exam_id", "=", "$examTable.id")
            ->where("$examUserTable.status", ExamUser::STATUS_NORMAL)
            ->selectRaw("$examUserTable.*")
            ->with(['exam', 'user', 'user.language'])
            ->orderBy("$examUserTable.id", "asc");
        if (!$ignoreTimeRange) {
            $baseQuery->whereRaw("if($examUserTable.end is not null, $examUserTable.end < '$now', $examTable.end < '$now')");
        }

        $size = 100;
        $minId = 0;
        $result = 0;
        while (true) {
            $logPrefix = sprintf('[%s], size: %s', __FUNCTION__, $size);
            $examUsers = (clone $baseQuery)->where("$examUserTable.id", ">", $minId)->limit($size)->get();
            if ($examUsers->isEmpty()) {
                do_log("$logPrefix, no more data..." . last_query());
                break;
            } else {
                do_log("$logPrefix, fetch exam users: {$examUsers->count()}");
            }
            $result += $examUsers->count();
            $now = Carbon::now()->toDateTimeString();
            $examUserIdArr = $uidToDisable = $messageToSend = [];
            foreach ($examUsers as $examUser) {
                $minId = $examUser->id;
                $examUserIdArr[] = $examUser->id;
                $uid = $examUser->uid;
                $currentLogPrefix = sprintf("$logPrefix, user: %s, exam: %s, examUser: %s", $uid, $examUser->exam_id, $examUser->id);
                $locale = $examUser->user->locale;
                if ($examUser->is_done) {
                    do_log("$currentLogPrefix, [is_done]");
                    $subjectTransKey = 'exam.checkout_pass_message_subject';
                    $msgTransKey = 'exam.checkout_pass_message_content';
                } else {
                    do_log("$currentLogPrefix, [will be banned]");
                    $subjectTransKey = 'exam.checkout_not_pass_message_subject';
                    $msgTransKey = 'exam.checkout_not_pass_message_content';
                    //ban user
                    $uidToDisable[] = $uid;
                }
                $subject =  nexus_trans($subjectTransKey, [], $locale);
                $msg = nexus_trans($msgTransKey, [
                    'exam_name' => $examUser->exam->name,
                    'begin' => $examUser->begin,
                    'end' => $examUser->end
                ], $locale);
                $messageToSend[] = [
                    'receiver' => $uid,
                    'added' => $now,
                    'subject' => $subject,
                    'msg' => $msg
                ];
            }
            DB::transaction(function () use ($uidToDisable, $messageToSend, $examUserIdArr) {
                ExamUser::query()->whereIn('id', $examUserIdArr)->update(['status' => ExamUser::STATUS_FINISHED]);
                Message::query()->insert($messageToSend);
                if (!empty($uidToDisable)) {
                    User::query()->whereIn('id', $uidToDisable)->update(['enabled' => User::ENABLED_NO]);
                }
            });
        }
        return $result;
    }




}