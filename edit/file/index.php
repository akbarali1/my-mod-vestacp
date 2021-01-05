<?php

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

<title>Edit file <?= htmlspecialchars($_REQUEST['path']) ?></title>
<meta charset="utf-8" /> 

<link href="/css/file_manager_editor.css" type="text/css" rel="stylesheet">
<script src="/js/cheef-editor/jquery/jquery-1.8.3.min.js"></script>
<script src="/js/cheef-editor/ace/ace.js"></script>

<script src="/js/cheef-editor/ace/mode-php.js"></script>
<script src="/js/cheef-editor/jquery-ace.min.js"></script>

<div id="message" style="display:none; position: absoulte;background-color: green; color: white; padding: 10px;"></div>
<div id="error-message" style="display:none; position: absoulte;background-color: red; color: white; padding: 10px;"></div>

<div id="edit-file-form" method="post">
<!-- input id="do-backup" type="button" onClick="javascript:void(0);" name="save" value="backup (ctrl+F2)" class="backup" / -->
<input type="submit" onClick="save_file();" value="Saqlash" class="save" />
<textarea class="editor" id="editor" rows="4" style="display:none;width: 100%; height: 100%;"><?=htmlentities($content)?></textarea>
</div>

<script type="text/javascript" src="/js/hotkeys.js"></script>
<script type="text/javascript">
    $('.editor').ace({ theme: 'monokai', lang: 'php' });

    var dcrt = $('#editor').data('ace');
    dcrt.editor.ace.getSession().setNewLineMode('unix');
    var aceInstance = dcrt.editor.ace;
    aceInstance.gotoLine(0);
    aceInstance.focus();

    var makeBackup = function() {
        var params = {
            action: 'backup',
            path:   '<?= $path ?>'
        };
        
        $.ajax({url: "/file_manager/fm_api.php", 
            method: "POST",
            data:   params,
            dataType: 'JSON',
            success: function(reply) {
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
    // 
    // Shortcuts
    // 
    
        function save_file(){
         var pagelink = window.location.href;
           var contents = $('textarea#editor').val();
           $.ajax({
               url: pagelink,
               type: "POST",
               data: { save:'save', contents:contents},
               beforeSend: function() { $("#chiqnm").attr("disabled", !0);},
               success: function (a) {
                var fadeTimeout = 3000;
                  if(a == 'saqlandi'){
                    $('#message').text('File save');
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
    
    
    shortcut.add("Ctrl+F2",function() {
        makeBackup();
    },{
        'type':             'keydown',
        'propagate':        false,
        'disable_in_input': false,
        'target':           document
    });
    
</script>
