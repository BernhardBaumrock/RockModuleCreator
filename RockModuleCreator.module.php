<?php namespace ProcessWire;
/**
 * RockModuleCreator
 *
 * @author Bernhard Baumrock, 13.01.2019
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class RockModuleCreator extends Process {
  public function ___execute() {
    /** @var InputfieldForm $form */
    $form = $this->modules->get('InputfieldForm');

    $url = 'https://github.com/BernhardBaumrock/RockMigrationsDemo/archive/master.zip';
    if($this->input->post->createNew) {
      // try to create the module
      $this->createModule();

      // if it was not successful we populate fields with input data
      $url = $this->input->post->moduleURL;
    }

    $form->add([
      'type' => 'fieldset',
      'label' => 'Create a new Module',
      'description' => 'This is a helper to quickly create new Modules based on the [Demo Module](https://github.com/BernhardBaumrock/RockMigrationsDemo).',
      'children' => [
        'moduleName' => [
          'type' => 'text',
          'label' => 'Name',
          'columnWidth' => 33,
          'value' => $this->input->post->moduleName,
        ],
        'moduleAuthor' => [
          'type' => 'text',
          'label' => 'Author',
          'columnWidth' => 33,
          'value' => $this->input->post->moduleAuthor,
        ],
        'moduleURL' => [
          'type' => 'text',
          'label' => 'Module Skeleton URL',
          'value' => $url,
          'columnWidth' => 34,
        ],
        'moduleIcon' => [
          'type' => 'icon',
          'label' => 'Icon',
          'value' => $this->input->post->moduleIcon,
        ],
      ],
    ]);

    $form->add([
      'createNew' => [
        'type' => 'submit',
        'value' => 'Create',
        'icon' => 'plus',
      ],
    ]);

    return $form->render();
  }

  /**
   * Create a new module.
   *
   * @return void
   */
  public function createModule() {
    $name = $this->sanitizer->alphanumeric($this->input->post->moduleName);
    $author = $this->sanitizer->text($this->input->post->moduleAuthor);
    $url = $this->sanitizer->url($this->input->post->moduleURL);
    $icon = str_replace("fa-", "", $this->sanitizer->text($this->input->post->moduleIcon));

    if(!$name) return $this->error('You must specify a valid module name!');
    if(!$url) return $this->error('You must specify a valid module url!');

    // check if the module already exists
    $dir = $this->config->paths->siteModules . $name;
    if(is_dir($dir)) return $this->error("Dir $dir already exists");

    // download module from given url
    require_once($this->config->paths->modules . "Process/ProcessModule/ProcessModuleInstall.php");
    $install = $this->wire(new ProcessModuleInstall());
    $oldpath = $install->downloadModule($url);
    if(!$oldpath) return $this->error("Error downloading module");

    // rename module folder
    $oldname = basename($oldpath);
    $newpath = str_replace($oldname, $name, $oldpath);
    $this->files->rename($oldpath, $newpath);

    // rename all files and replace content
    foreach($this->files->find($newpath) as $file) {
      $newfile = str_replace($oldname, $name, $file);
      $this->files->rename($file, $newfile);

      // get file content
      $content = file_get_contents($newfile);

      $content = str_replace($oldname, $name, $content); // name
      $content = str_replace('#author#', $author, $content); // author
      $content = str_replace('#date#', date("d.m.Y"), $content); // date
      $content = str_replace('#icon#', $icon, $content); // date

      // save file
      file_put_contents($newfile, $content);
    }

    // redirect to the modules install page
    $this->session->redirect($this->pages->get(2)->url . "module/?reset=1");
  }
}

