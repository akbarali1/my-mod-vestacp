<?php
  $LastModified_unix = 1602876667; // время последнего изменения страницы
  $LastModified = gmdate("D, d M Y H:i:s \G\M\T", $LastModified_unix);
  $IfModifiedSince = false;
  if (isset($_ENV['HTTP_IF_MODIFIED_SINCE']))
      $IfModifiedSince = strtotime(substr($_ENV['HTTP_IF_MODIFIED_SINCE'], 5));
  if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
      $IfModifiedSince = strtotime(substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 5));
  if ($IfModifiedSince && $IfModifiedSince >= $LastModified_unix) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
      exit;
  }
  header('Last-Modified: '. $LastModified);
  
    include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");
    $user = $_SESSION['user'];
    
    // Check module activation
    if (!$_SESSION['FILEMANAGER_KEY']) {
        $_SESSION['request_uri'] = $_SERVER['REQUEST_URI'];
        header("Location: /login/");
        exit;
    }
    
    // Check login_as feature
    if (($_SESSION['user'] == 'admin') && (!empty($_SESSION['look']))) {
        $user=$_SESSION['look'];
    }
        if (!empty($_REQUEST['path'])) {
            $content = '';
            $path = $_REQUEST['path'];
            if (!empty($_POST['save'])) {
                $fn = tempnam ('/tmp', 'vst-save-file-');
                if ($fn) {
                    $contents = $_POST['contents'];
                    $contents = preg_replace("/\r/", "", $contents);
                    $f = fopen ($fn, 'w+');
                    fwrite($f, $contents);
                    fclose($f);
                    chmod($fn, 0644);
    
                    if ($f) {
                        exec (VESTA_CMD . "v-copy-fs-file {$user} {$fn} ".escapeshellarg($path), $output, $return_var);
                        $error = check_return_code($return_var, $output);
                        if ($return_var != 0) {
                            print('<p style="color: white">Error while saving file</p>');
                            exit;
                        }
                    }
                    unlink($fn);
                }
                echo 'saqlandi';
                die;
            }
    
            exec (VESTA_CMD . "v-open-fs-file {$user} ".escapeshellarg($path), $content, $return_var);
            if ($return_var != 0) {
                print 'Error while opening file'; // todo: handle this more styled
                exit;
            }
            $content = implode("\n", $content)."\n";
            
        } else {
            $content = '';
        }
       
    ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Edit file <?= htmlspecialchars($_REQUEST['path']) ?></title>
    <script src="/js/cheef-editor/jquery/jquery-1.8.3.min.js"></script> <!-- load emmet code and snippets compiled for browser -->
    <script src="https://cloud9ide.github.io/emmet-core/emmet.js"></script>
    <script src="/js/kitchen-sink/require.js"></script>
    <script type="text/javascript" src="/js/hotkeys.js"></script>
    <style type="text/css" media="screen">
      body {
      overflow: hidden;
      }
      #editor { 
      margin: 0;
      position: absolute;
      top: 0;
      bottom: 0;
      left: 0;
      right: 0;
      }
    </style>
  </head>
  <body>
    <input type="submit" onclick="makeBackup();" value="Backup" style="display:block;position: relative;background-color: #2049C6;color: white;padding: 10px;z-index: 999999999999999999999;float: right;margin-top: 80px;margin-right: 15px;">
    <input type="submit" onClick="save_file();" value="Saqlash" style="display:block;position: relative;background-color: #00FF3A;color: black;padding: 10px;z-index: 999999999999999999999;float: right;margin-top: 133px;margin-right: -69px;" />
    <div id="message" style="display:none;position: relative;background-color: green;color: white;padding: 10px;z-index: 999999999999999999999;float: right;"></div>
    <div id="bajarilmoqda" style="display:none;position: relative;background-color: yellow;color: black;padding: 10px;z-index: 999999999999999999999;float: right;">Berilgan vazifa qayta ishlanmoqda</div>
    <div id="error-message" style="display:none; position: relative;background-color: red; color: white; padding: 10px;z-index: 999999999999999999999;float: right;"></div>
    <textarea  name="editor" style="display:none;width: 100%; height: 100%;"><?=htmlentities($content)?></textarea>
    <div id="editor"></div>
    <script>
      // setup paths
      require.config({paths: { "ace" : "../../js/lib/ace"}});
      // load ace and extensions
      require(["ace/ace", "ace/ext/emmet", "ace/ext/settings_menu"], function(ace) {
          var editor = ace.edit("editor");
             editor.setOptions({
              copyWithEmptySelection: true,
            //  enableMultiselect: true,
            });
          editor.setTheme("ace/theme/tomorrow_night_eighties");
          var textarea = $('textarea[name="editor"]').hide();
           ace.require('ace/ext/settings_menu').init(editor);
          editor.session.setMode("ace/mode/php");
          // enable emmet on the current editor
          editor.setOption("enableEmmet", true);
      editor.getSession().setValue(textarea.val());
      editor.getSession().on('change', function(){
      textarea.val(editor.getSession().getValue());
      });
      
      editor.commands.addCommand({
        name: "showKeyboardShortcuts",
        bindKey: {win: "Ctrl-Alt-h", mac: "Command-Alt-h"},
        exec: function(editor) {
            ace.config.loadModule("ace/ext/keybinding_menu", function(module) {
                module.init(editor);
                editor.showKeyboardShortcuts()
            })
        }
      })
   //   editor.execCommand("showKeyboardShortcuts");
       
       /*editor.setOption("wrap", true);
      	editor.commands.addCommands([{
      		name: "showSettingsMenu",
      		bindKey: {win: "Ctrl-q", mac: "Ctrl-q"},
      		exec: function(editor) {
      			editor.showSettingsMenu();
      		},
      		readOnly: true
      	}]); 
      	*/
     });
      var makeBackup = function() {
         var params = {
              action: 'backup',
              path:   '<?= $path ?>'
          };
          
          $.ajax({url: "/file_manager/fm_api.php", 
              method: "POST",
              data:   params,
              dataType: 'JSON',
              beforeSend: function() {$('#bajarilmoqda').show();},
              success: function(reply) {
               $('#bajarilmoqda').hide();
                  var fadeTimeout = 3000;
                  if (reply.result) {
                      $('#message').text('File backed up as ' + reply.filename);
                      clearTimeout(window.msg_tmt);
                      $('#message').show();
                      window.msg_tmt = setTimeout(function() {$('#message').fadeOut();}, fadeTimeout);
                  }
                  else {
                      $('#error-message').text(reply.message);
                      clearTimeout(window.errmsg_tmt);
                      $('#error-message').show();
                      window.errmsg_tmt = setTimeout(function() {$('#error-message').fadeOut();}, fadeTimeout);
                  }
              }
          });
      }
      
      $('#do-backup').on('click', function(evt) {
          evt.preventDefault();
          makeBackup();
      });
      
          function save_file(){
           var pagelink = window.location.href;
             var contents = $('textarea[name="editor"]').val();
             $.ajax({
                 url: pagelink,
                 type: "POST",
                 data: { save:'save', contents:contents},
                 beforeSend: function() {$('#bajarilmoqda').show();},
                 success: function (a) {
                  $('#bajarilmoqda').hide();
                  var fadeTimeout = 3000;
                    if(a == 'saqlandi'){
                      $('#message').text('Fayl muvafaqiyatli saqlandi');
                      clearTimeout(window.msg_tmt);
                      $('#message').show();
                      window.msg_tmt = setTimeout(function() {$('#message').fadeOut();}, fadeTimeout);
                    }else{
                     $('#error-message').text('Fayl saqlanmadi nimagaligini bilmadim');
                      clearTimeout(window.errmsg_tmt);
                      $('#error-message').show();
                      window.errmsg_tmt = setTimeout(function() {$('#error-message').fadeOut();}, fadeTimeout);
                    }
                 }
             });
         }
      
      shortcut.add("Ctrl+s",function() {
        save_file();
      },{
          'type':             'keydown',
          'propagate':        false,
          'disable_in_input': false,
          'target':           document
      });
      
      shortcut.add("Shift+f12",function() {
        save_file();
      },{
          'type':             'keydown',
          'propagate':        false,
          'disable_in_input': false,
          'target':           document
      });
      
      shortcut.add("Ctrl+b",function() {
          makeBackup();
      },{
          'type':             'keydown',
          'propagate':        false,
          'disable_in_input': false,
          'target':           document
      });
    </script>
  </body>
</html>