<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Models\User;
use Illuminate\Http\Request;
use DTApi\Models\Distance;
use DTApi\Repository\BookingRepository;
use Auth;
/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;
    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $requestUser = User::find($request->get('user_id'));
        $currentUser = Auth::user();
        #Put Right Condition and Readable Code
        if($currentUser->id == $requestUser->id) {
            $response = $this->repository->getUsersJobs($this->currentUser->id);
        }
        elseif($requestUser->user_type == 'ADMIN_ROLE_ID' || $requestUser->user_type == 'SUPERADMIN_ROLE_ID')
        {
            $response = $this->repository->getAll($request);
        }
        #return response
        return $response;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        #Cannot User find on class, but only on Eloquent Model
        return $this->repository->with('translatorJobRel.user');
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $requestUser = User::find($request->get('user_id'));
        return $this->repository->store($requestUser, $request->all());
     }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $requestUser = User::find($request->get('user_id'));
        return $this->repository->updateJob($id, array_except($request->all(), ['_token', 'submit']), $requestUser);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        return $this->repository->storeJobEmail($request->all());
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        $currentUser = Auth::user();
        if($currentUser->id = $request->get('user_id')) {
            return $this->repository->getUsersJobsHistory($currentUser->id, $request);
        }
        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $requestUser = User::find($request->get('user_id'));
        return $this->repository->acceptJob($request->all(), $requestUser);
    }

    public function acceptJobWithId(Request $request)
    {
        $requestUser = User::find($request->get('user_id'));
        return $this->repository->acceptJobWithId($request->get('job_id'), $requestUser);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $requestUser = User::find($request->get('user_id'));
        return $this->repository->cancelJobAjax($request->all(), $requestUser);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        return $this->repository->endJob($request->all());
    }

    public function customerNotCall(Request $request)
    {
        return $this->repository->customerNotCall($request->all());
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $requestUser = User::find($request->get('user_id'));
        return $this->repository->getPotentialJobs($requestUser);
    }

    public function distanceFeed(Request $request)
    {
        #Improving Conditions
        $data = $request->all();
        (isset($data['distance'])) ? $distance = $data['distance'] : $distance = "";
        (isset($data['time'])) ? $time = $data['time'] : $time = "";
        (isset($data['jobid']))? $jobid = $data['jobid'] : '';
        (isset($data['session_time'])) ? $session = $data['session_time']:$session = "";

        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        } else $flagged = 'no';

        ($data['manually_handled'] == 'true') ? $manually_handled = 'yes' : $manually_handled = 'no';
        ($data['by_admin'] == 'true') ?  $by_admin = 'yes' : $by_admin = 'no';
        (isset($data['admincomment']) && $data['admincomment'] != "") ? $admincomment = $data['admincomment'] : $admincomment = "";
        ($time || $distance) ? Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time)) : '' ;
        ($admincomment || $session || $flagged || $manually_handled || $by_admin)? Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin)) : '';
        return 'Record updated!';
    }

    public function reopen(Request $request)
    {
        return $this->repository->reopen($request->all());
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');
        return json_encode(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $this->repository->jobToData($job);
        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return json_encode(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return json_encode(['success' => $e->getMessage()]);
        }
    }

}
