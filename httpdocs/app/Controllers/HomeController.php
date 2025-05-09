<?php
/**
* app/Controllers/HomeController.php
* Controller for home page and static pages
**/

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Services\AuthService;

class HomeController extends Controller {
    private $post;
    private $authService;
    
    public function __construct(\PDO $db) {
        parent::__construct($db);
        $this->post = new Post();
        $this->authService = new AuthService();
    }
    
    /**
     * Display home page
     * 
     * @return string Rendered view
     */
    public function index() {
        // If user is logged in, redirect to feed
        if ($this->authService->check()) {
            return $this->redirect('/feed');
        }
        
        // For guests, show the home page
        return $this->render('home', [
            'title' => 'Welcome to UniSocial',
            'description' => 'Connect with university students and share your experiences'
        ]);
    }
    
    /**
     * Display about page
     * 
     * @return string Rendered view
     */
    public function about() {
        return $this->view('pages/about');
    }
    
    /**
     * Display terms of service page
     * 
     * @return string Rendered view
     */
    public function terms() {
        return $this->view('pages/terms');
    }
    
    /**
     * Display privacy policy page
     * 
     * @return string Rendered view
     */
    public function privacy() {
        return $this->view('pages/privacy');
    }
    
    /**
     * Display help/FAQ page
     * 
     * @return string Rendered view
     */
    public function help() {
        return $this->view('pages/help');
    }
    
    /**
     * Display contact page
     * 
     * @return string Rendered view
     */
    public function contact() {
        return $this->view('pages/contact');
    }
    
    /**
     * Process contact form submission
     * 
     * @return mixed Redirect or JSON response
     */
    public function submitContact() {
        // Get input data
        $data = $this->getInputData();
        
        // Validate input
        $rules = [
            'name' => 'required|max:100',
            'email' => 'required|email|max:100',
            'subject' => 'required|max:200',
            'message' => 'required|min:10'
        ];
        
        $errors = $this->validate($data, $rules);
        
        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                return $this->error('Validation failed', $errors);
            }
            
            $this->setFlashMessage('Please fix the errors below', 'error');
            $_SESSION['validation_errors'] = $errors;
            $_SESSION['old_input'] = $data;
            
            return $this->redirect('/contact');
        }
        
        // Process contact form (in a real app, we would send an email)
        // Here we'll just simulate success
        
        if ($this->isAjaxRequest()) {
            return $this->success([], 'Your message has been sent! We\'ll get back to you soon.');
        }
        
        $this->setFlashMessage('Your message has been sent! We\'ll get back to you soon.', 'success');
        return $this->redirect('/contact');
    }
    
    /**
     * Display 404 page
     * 
     * @return string Rendered view
     */
    public function notFound() {
        // Set 404 status code
        http_response_code(404);
        
        return $this->view('errors/404');
    }
    
    /**
     * Display 500 page
     * 
     * @return string Rendered view
     */
    public function serverError() {
        // Set 500 status code
        http_response_code(500);
        
        return $this->view('errors/500');
    }
    
    /**
     * Display maintenance page
     * 
     * @return string Rendered view
     */
    public function maintenance() {
        // Set 503 status code
        http_response_code(503);
        
        return $this->view('errors/maintenance');
    }
}