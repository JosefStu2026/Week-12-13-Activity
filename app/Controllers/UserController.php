<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class UserController extends BaseController
{

    protected $userModel;

    public function __construct()
    {
        // Instantiating the core database engine access wrapper
        $this->userModel = new UserModel();
    }

    // 1. READ: Display directory with integrated search and safe pagination
    public function index()
   {
    // 1. Capture user state context
    $searchKeyword = $this->request->getGet('search') ?? '';
    $currentPage   = $this->request->getGet('page') ?? '1';

    // 2. Generate a highly unique, deterministic Cache Key
    // MD5 hashing prevents long string spaces and ensures a clean file name
    $cacheKey = 'users_list_' . md5($searchKeyword . '_' . $currentPage);

    // 3. Attempt to intercept via the native Cache Service Container
    if (! $cachedData = cache($cacheKey)) {
        
        // --- CACHE MISS ENGINE ACTIONS ---
        // Cache was not found. We must explicitly execute database read steps.
        log_message('debug', 'CACHE MISS: Executing raw database operations for key: ' . $cacheKey);

        $query = $this->userModel;
        
        if (!empty($searchKeyword)) {
            $query = $query->like('name', $searchKeyword)->orLike('email', $searchKeyword);
        }

        // Fetch our paginated collection segment
        $users = $query->paginate(3, 'default');
        
        // Wrap our combined display requirements inside an extraction payload array
        $cachedData = [
            'users'         => $users,
            'searchKeyword' => $searchKeyword,
            // CRITICAL ARCHITECTURAL STEP: 
            // We must serialize and cache the pager string output markup so we don't break our view engine!
            'pager_links'   => $this->userModel->pager->links('default', 'bootstrap_cms')
        ];

        // Save this compiled payload matrix into our secure cache system storage for 5 minutes (300 seconds)
        cache()->save($cacheKey, $cachedData, 300);
    } else {
        // --- CACHE HIT ENGINE ACTIONS ---
        log_message('debug', 'CACHE HIT: Intercepted database workload safely using key: ' . $cacheKey);
    }

    // Pass the cached matrix directly down into your view layout pipeline
    return view('users/index', $cachedData);
}
    // 2. READ: Display Registration Screen
    public function create()
    {
        return view('users/create');
    }

    // 3. WRITE: Validate inputs, handle file secure moving, record to DB
    public function store()
{
    // 1. Enforce validation matrix
    $rules = [
        'name'   => 'required|min_length[3]|max_length[100]',
        'email'  => 'required|valid_email|is_unique[users.email]',
        'avatar' => 'uploaded[avatar]|max_size[avatar,2048]|ext_in[avatar,png,jpg,jpeg]|mime_in[avatar,image/png,image/jpeg]'
    ];

    if (!$this->validate($rules)) {
        return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
    }

    // 2. Extract asset stream securely
    $file = $this->request->getFile('avatar');

    if ($file->isValid() && !$file->hasMoved()) {
        $newName = $file->getRandomName(); // Masking filename to avoid directory execution exploits [cite: 102]
        
        // Wrap database state modifications and filesystem writes into exception shields
        try {
            // Move file to our web-accessible destination directory
            $file->move(FCPATH . 'uploads/', $newName);
            
            $payloadData = [
                'name'   => $this->request->getPost('name'),
                'email'  => $this->request->getPost('email'),
                'avatar' => $newName
            ];

            // Record user state metadata to database
            $this->userModel->insert($payloadData);

            // 3. EXECUTE EMAIL PIPELINE TRANSACTION
            $this->sendNotificationEmail($payloadData);

            return redirect()->to('users')->with('success', 'User execution recorded cleanly.');

        } catch (\Exception $e) {
            // Write detailed trace down to secure system logs inside writable/logs/ [cite: 208, 209]
            log_message('error', 'Critical User Store Failure: ' . $e->getMessage());

            // Clean fallback: Present user with a friendly error flash message instead of a raw crash trace [cite: 36, 207]
            return redirect()->back()->withInput()->with('errors', ['system' => 'Failed to securely commit data processing cycles.']);
        }
    }

    return redirect()->back()->withInput()->with('errors', ['avatar' => 'Target file instance state invalid.']);
}

// Private helper to isolate email generation mechanics cleanly
private function sendNotificationEmail(array $userData)
{
    // Pull the email driver from the services container container [cite: 116, 117]
    $email = \Config\Services::email();

    $email->setTo($userData['email']); // Dynamic customer address destination [cite: 120]
    $email->setSubject('System Access Provisioned // Core Engine'); // [cite: 121]
    
    // Inject dynamic array payload parameters into email template layout view 
    $email->setMessage(view('email/welcome', [
        'name'  => $userData['name'],
        'email' => $userData['email']
    ]));

    // Dispatch payload stream over network architecture
    if (!$email->send()) {
        // Log soft warning diagnostics if network transport drops out
        log_message('warning', 'Notification failed delivery sequence: ' . $email->printDebugger(['headers'])); // [cite: 124]
    }
}

    // 4. WRITE: Safely delete record from system state
    public function delete($id = null)
    {
    $record = $this->userModel->find($id);
    
    if (!$record) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    // Clean out disk asset traces
    $filePath = FCPATH . 'uploads/' . $record['avatar'];
    if (file_exists($filePath) && $record['avatar'] !== 'default-avatar.png') {
        unlink($filePath);
    }

    // Execute the database row scrub step
    $this->userModel->delete($id);

    // CRITICAL ENGINE RULE: Nuke the user directory caches completely!
    // Since we hash our cache keys uniquely based on pagination positions, the fastest way 
    // to prevent display bugs is to cleanly wipe the cache pool entirely
    cache()->clean(); 

    return redirect()->to('users')->with('success', 'State scrubbed and cache flushed.');
      }
    }
