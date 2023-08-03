<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;
use Illuminate\Support\Facades\Auth;
use DTApi\Models\{
    Job,
    Distance
};

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
        if($user_id = $request->get('user_id')) {
            $response = $this->repository->getUsersJobs($user_id);
        } elseif(Auth::user()->user_type == env('ADMIN_ROLE_ID') || Auth::user()->user_type == env('SUPERADMIN_ROLE_ID')) {
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);
        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $response = $this->repository->store(Auth::user(), $request->all());

        return response($response);
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $response = $this->repository->updateJob($id, array_except($request->all(), ['_token', 'submit']), Auth::user());

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');
        $response = $this->repository->storeJobEmail($request->all());

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $response = $this->repository->acceptJob($request->all(), Auth::user());

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $response = $this->repository->acceptJobWithId($data, Auth::user());

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $response = $this->repository->cancelJobAjax($request->all(), Auth::user());
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $response = $this->repository->endJob($request->all());

        return response($response);
    }

    public function customerNotCall(Request $request)
    {
        $response = $this->repository->customerNotCall($request->all());

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $response = $this->repository->getPotentialJobs(Auth::user());
        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $distance = "" ;
        $time = "" ;
        $session = "" ;
        $flagged = 'no';
        $manually_handled = 'no';
        $by_admin = 'no';
        $admincomment = "";
        
        if (isset($data['distance']) && $data['distance'] != "") {
            $distance = $data['distance'];
        }
        
        if (isset($data['time']) && $data['time'] != "") {
            $time = $data['time'];
        } 
        if (isset($data['jobid']) && $data['jobid'] != "") {
            $jobid = $data['jobid'];
        }

        if (isset($data['session_time']) && $data['session_time'] != "") {
            $session = $data['session_time'];
        } 

        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        }
        
        if ($data['manually_handled'] == 'true') {
            $manually_handled = 'yes';
        } 

        if ($data['by_admin'] == 'true') {
            $by_admin = 'yes';
        }

        if (isset($data['admincomment']) && $data['admincomment'] != "") {
            $admincomment = $data['admincomment'];
        } 
        
        if ($time || $distance) {
            $affectedRows = Distance::whereJobId($jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {
            $affectedRows1 = Job::whereJobId($jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));
        }

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $response = $this->repository->reopen($request->all());
        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
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
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
