<?php
/*
Plugin Name: wp2blosxom
Plugin URI: http://fliptoad.wordpress.com/projects/wp2blosxom/
Description: generate a zip file containing entries as one would see in blosxom organized in directories according to the first category seen.
Version: 1.1
Author: Jacob Gelbman
Author URI: http://fliptoad.wordpress.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

http://www.gnu.org/licenses/gpl.txt

You should have received a copy of the GNU General Public License
along with this program; if not, write to the 

Free Software Foundation, Inc.,   
51 Franklin St, Fifth Floor,   
Boston, MA  02110-1301  USA


TODO
====

-   add pages to pages/ in zip
-   allow different meta-date labels
-   perhaps this should be in the regular wordpress export settings page
-   import


REQUIREMENTS
============

-   write access to wp-content folder
-   zip program in path and the ability to have php use system()
-   I've only tried this on wp v2.6 and v2.7 so i dont know if this will work
    on other versions


CHANGES
=======

v1.1 20081217
-   added recursive_mkdir to make it compatible with php 4

v1.0 20081215
-   first release

*/

add_action('admin_menu', 'wp2blosxom_menu');

function wp2blosxom_menu() {
    add_menu_page('wp2blosxom','wp2blosxom', 8, __FILE__,
        'wp2blosxom_page');
}

/* displays a page that allows one to push a button and a download link to
   a zip file containing blosxom like files of ones posts will appear. There
   should also be a way to delete the .zip files generated  */
function wp2blosxom_page() {
    echo '<div class="wrap">';

    //make the directory to hold my blosxom zips and temp workdirs
    $wp2blosxom_dir = 'wp2blosxom';
    $wp2blosxom_rel = get_option('siteurl') . "/wp-content/$wp2blosxom_dir";
    $wp2blosxom_absdir = ABSPATH . "/wp-content/$wp2blosxom_dir";
    if (!is_dir($wp2blosxom_absdir)) {
        if (!mkdir($wp2blosxom_absdir)) {
            echo "<p>couldnt mkdir $wp2blosxom_absdir</p>";
            return;
        }
    }
    
    //options
    $opt = array(
        'add_dates' => 'N',
        );
    
    //fill options from database
    $opt['add_dates'] = get_option('add_dates');
    
    //handle post data to generate the blosxom .zip file
    if ($_POST['wp2blosxom_hidden'] == 'Y') {
        $opt['add_dates'] = isset($_POST['add_dates']) ? 'Y' : 'N';
        update_option('add_dates', $opt['add_dates']);
        
        gen_blosxom_zip($wp2blosxom_absdir, $opt);
    }
    
    //display title info of page
    echo '<h2>Export to Blosxom</h2>';
    
    //display list of previously generated blosxom zip files
    display_blosxom_zips($wp2blosxom_absdir, $wp2blosxom_rel);
    
    //display form to kick off blosxom zip generation
    echo
        '<form name="wp2blosxom" method="post" action="',
        str_replace('%7E', '~', $_SERVER['REQUEST_URI']), '">',
        
        '<input type="hidden" name="wp2blosxom_hidden" value="Y" />',
        
        '<input type="checkbox", name="add_dates" ',
        $opt['add_dates'] == 'Y' ? 'checked' : '',
        ' /> Add meta dates to the blosxom entries (requires the ',
        'entriescache plugin)<br />',
        
        '<input type="submit" name="submit" value="generate blosxom zip" />',
        
        '</form>';
    
    echo '</div>';
}

function gen_blosxom_zip($wp2blosxom_absdir, $opt) {
    echo "<p>creating blosxom zip file</p>";
    
    //make workdir
    $workdir = 'entries';
    $workdir_abs = $wp2blosxom_absdir . '/' . $workdir;
    if (!is_dir($workdir_abs)) {
        if (!mkdir($workdir_abs)) {
            echo "<p>couldnt mkdir $workdir_abs</p>";
            return;
        }
    }
    
    //loop through all the entries
    query_posts('posts_per_page=-1');
    while(have_posts()) {
        the_post();
                
        $post = get_post(get_the_ID());
        $post_info['slug'] = $post->post_name;
        $post_info['title'] = $post->post_title;
        $post_info['content'] = get_the_content();
        $post_info['date'] = get_the_time('n/j/Y H:i:s');
        $cats = get_the_category();
        $post_info['cat'] = get_category_parents(
            $cats[0]->cat_ID, false, '/', true);
        
        make_blosxom_entry($workdir_abs, $post_info, $opt);
        
    }
    
    //generate .zip
    make_blosxom_zip($wp2blosxom_absdir, $workdir);
    
    echo "<p>done!</p>";
}

/* creates a file in a category-based directory under the workdir  */
function make_blosxom_entry($workdir, $post_info, $opt) {
    //make the category-based subdirectories
    $entry_dir = $workdir . '/' . $post_info['cat'];
    if (!is_dir($entry_dir)) {
        if (!mkdir_recursive($entry_dir)) {
            echo "<p>couldnt mkdir $entry_dir</p>";
            return;
        }
    }
    
    //write the file
    $file = $entry_dir . $post_info['slug'] . '.txt';
    $fh = fopen($file, 'w');
    if (!$fh) {
        echo "<p>couldnt open $file for writing!</p>";
        return;
    }
    
    fwrite($fh, $post_info['title'] . "\n");
    if ($opt['add_dates'] == 'Y')
        { fwrite($fh, 'meta-creation_date: ' . $post_info['date'] . "\n"); }
    fwrite($fh, "\n");
    fwrite($fh, $post_info['content'] . "\n");
    
    fclose($fh);
    
    //change modification time
    touch($file, strtotime($post_info['date']));
}

function make_blosxom_zip($wp2blosxom_absdir, $workdir) {
    /* php ZipArchive class is not available on all systems, might as well
       use zip from the command line. I hope your system has it. Also the 
       shell_exec function requires that you not run php in safe mode  */
    
    $status = 1;
    
    $old_cwd = getcwd();
    chdir($wp2blosxom_absdir);
    
    $file = 'wp2blosxom.' . date('Ymd\THis') . '.zip';
    
    $command = "zip -rmq $file $workdir";
    system($command, $retval);
    if ($retval != 0) {
        echo "<p>$command failed with value $retval</p>";
        $status = 0;
    }
    
    chdir($old_cwd);
    
    return $status;
}

/* display all the zips created before, allow for download and deletion  */
function display_blosxom_zips($wp2blosxom_absdir, $wp2blosxom_rel) {
    //process form data to remove the selected zips
    process_wp2blosxom_rm($wp2blosxom_absdir);
    
    $num_zips = 0;

    //display form to remove previous .zip files
    $content
        = '<hr /><form name="wp2blosxom_rm" method="post" action="'
        . str_replace('%7E', '~', $_SERVER['REQUEST_URI']) . '">'
        . '<input type="hidden" name="wp2blosxom_rm_hidden" value="Y" />';
    
    //open directory containing all the zips
    $dh = opendir($wp2blosxom_absdir);
    if (!$dh) {
        echo "<p>couldnt open $wp2blosxom_absdir</p>";
        return;
    }
    
    $content .= '<table class="widefat"><caption>previously generated '
             .  'blosxom zips</caption><thead><tr><th>zip file</th>'
             .  '<th>delete?</th></thead><tbody>';
    
    //output a link to download the zip file and a checkbox to delete it
    while (($file = readdir($dh)) !== false) {
        if (!preg_match('/\.zip$/i', $file))
            { continue; }
        
        $num_zips++;
        $content
            .= "<tr><td><a href=\"$wp2blosxom_rel/$file\">$file</a>"
            .  '</td><td><input type="checkbox" name="rmzips[]" value="'
            .  $file . '" /></td></tr>';
    }
    
    closedir($dh);
   
    //display a submit button to delete all selected zip files
    $content
        .= '</tbody></table><p><input type="submit" name="submit" '
        .  'value="delete selected zip files" /></p>'
        .  '</form><hr />';
    
    if ($num_zips != 0) { echo $content; }
}

function process_wp2blosxom_rm($wp2blosxom_absdir) {
    if ($_POST['wp2blosxom_rm_hidden'] == 'Y') {
        $remove_zips = $_POST['rmzips'];
        
        foreach ($remove_zips as $zip) {
            if (!preg_match('/^wp2blosxom\.\d{8}T\d{6}\.zip$/i', $zip)) {
                echo "<p>$zip doesnt look correct, skipping.</p>";
                continue;
            }
            
            if (!unlink($wp2blosxom_absdir . '/' . $zip)) {
                echo "<p>couldnt unlink $zip</p>";
                continue;
            }
            
            echo "<p>removed $zip</p>";
        }
    }
}

function mkdir_recursive($pathname, $mode=0777) {
    is_dir(dirname($pathname)) || mkdir_recursive(dirname($pathname), $mode);
    return is_dir($pathname) || @mkdir($pathname, $mode);
}

?>