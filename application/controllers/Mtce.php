<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Form_validation form_validation
 * @property CI_Session session
 * @property Tasks tasks
 * @property App app
 * @property CI_Input input
 */
class Mtce extends Application {

    private $items_per_page = 10;


    public function index() {
//        $this->data['pagetitle'] = 'BrayDong TODO List Maintenance';
//        $tasks = $this->tasks->all(); // get all the tasks
//        $this->show_page($tasks);

        $this->page(1);
    }

    private function show_page($tasks) {
        $this->data['pagetitle'] = 'BrayDong TODO List Maintenance';
        // build the task presentation output
        $result = ''; // start with an empty array
        $role = $this->session->userdata('userrole');

        foreach ($tasks as $task) {

            if (!empty($task->status))
                $task->status = $this->app->status($task->status);
            $result .= $this->parser->parse('oneitem', (array) $task, true);
        }

        $this->data['pagetitle'] = 'TODO List Maintenance ('. $role . ')';
        $this->data['display_tasks'] = $result;

        // and then pass them on
        $this->data['pagebody'] = 'itemlist';
        $this->render();

    }

    // Extract & handle a page of items, defaulting to the beginning
    function page($num = 1) {

        $records = $this->tasks->all(); // get all the tasks
        $tasks = array(); // start with an empty extract

        // use a foreach loop, because the record indices may not be sequential
        $index = 0; // where are we in the tasks list
        $count = 0; // how many items have we added to the extract
        $start = ($num - 1) * $this->items_per_page;
        foreach($records as $task) {
            if ($index++ >= $start) {
                $tasks[] = $task;
                $count++;
            }
            if ($count >= $this->items_per_page) break;
        }

        $this->data['pagination'] = $this->pagenav($num);
        $this->show_page($tasks);

    }

    // Build the pagination navbar
    private function pagenav($num) {
        $lastpage = ceil($this->tasks->size() / $this->items_per_page);
        $parms = array(
            'first' => 1,
            'previous' => (max($num-1,1)),
            'next' => min($num+1,$lastpage),
            'last' => $lastpage
        );
        return $this->parser->parse('itemnav',$parms,true);
    }


    // Task 10
    // Initiate adding a new task
    public function add()
    {
        $task = $this->tasks->create();
        $this->session->set_userdata('task', $task);
        $this->showit();
    }

    // initiate editing of a task
    public function edit($id = null)
    {
        if ($id == null)
            redirect('/mtce');
        $task = $this->tasks->get($id);
        $this->session->set_userdata('task', $task);
        $this->showit();
    }

    // Render the current DTO
    private function showit()
    {
        $this->load->helper('form');
        $task = $this->session->userdata('task');
        $this->data['id'] = $task->id;

        // if no errors, pass an empty message
        if ( ! isset($this->data['error']))
            $this->data['error'] = '';

        $fields = array(
            'ftask'      => form_label('Task description') . form_input('task', $task->task),
            'fpriority'  => form_label('Priority') . form_dropdown('priority', $this->app->priority(), $task->priority),
            'zsubmit'    => form_submit('submit', 'Update the TODO task'),
        );
        $this->data = array_merge($this->data, $fields);

        $this->data['pagebody'] = 'itemedit';
        $this->render();
    }

    // Forget about this edit
    function cancel() {
        $this->session->unset_userdata('task');
        redirect('/mtce');
    }

    // build a suitable error mesage
    private function alert($message, $type='success') {
        $this->load->helper('html');
        $this->data['error'] = heading($message,3);
    }

    // handle form submission
    public function submit()
    {
        // setup for validation
        $this->load->library('form_validation');
        $this->form_validation->set_rules($this->tasks->rules());

        // retrieve & update data transfer buffer
        $task = (array) $this->session->userdata('task');
        $task = array_merge($task, $this->input->post());
        $task = (object) $task;  // convert back to object
        $this->session->set_userdata('task', (object) $task);

        // validate away
        if ($this->form_validation->run())
        {
            if (empty($task->id))
            {
                $task->id = $this->tasks->highest() + 1;
                $this->tasks->add($task);
                $this->alert('Task ' . $task->id . ' added', 'success');
            } else
            {
                $this->tasks->update($task);
                $this->alert('Task ' . $task->id . ' updated', 'success');
            }
        } else
        {
            $this->alert('<strong>Validation errors!<strong><br>' . validation_errors(), 'danger');
        }
        $this->showit();
    }

}
