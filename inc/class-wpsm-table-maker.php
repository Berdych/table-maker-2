<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPSM_Table_Maker
{
	private $version;
	private $page_slug;
	private $page_hook;
	private $base_url;
	private $db;
	private $user_caps;

	function __construct($_version, $_base_url = false) {
		$this->load_dependencies();
		$this->version = $_version;
		$this->page_slug = 'wpsm_table_maker';
		$this->db = WPSM_DB_Table::get_instance();
		$this->user_caps = 'manage_categories';
		add_action('admin_menu', array($this, 'add_menu_items'));
		add_action('admin_enqueue_scripts', array($this, 'backend_enqueue'));
		add_action('current_screen', array($this, 'handle_requests'));
		//add_action('current_screen', array($this, 'xml_download'));
		add_action('admin_notices', array($this, 'admin_notices') );
		add_shortcode('wpsm_comparison_table', array($this, 'comparison_table_callback'));
		add_action('init', array($this, 'wpsm_table_frontend_scripts') );
		add_action('wp_enqueue_scripts', array($this, 'wpsm_table_frontend_styles'));

		if(!$_base_url)
			$this->base_url = plugins_url( '', dirname(__FILE__) );
		else
			$this->base_url = $_base_url;
	}

	private function load_dependencies(){
		require_once 'class-wpsm-list-table.php';
		require_once 'class-wpsm-db-table.php';
		require_once 'class-wpsm-table-xml.php';
	}

	public function add_menu_items() {
		$user_caps = apply_filters('wpsmt_user_cappabilities', $this->user_caps);
		$this->page_hook = add_menu_page( __('Table Maker', 'wpsm-tableplugin'), __('Table Maker', 'wpsm-tableplugin'), $user_caps, $this->page_slug, array($this, 'print_page'), $this->base_url . "/img/icon.png" );
	}

	public function wpsm_table_frontend_scripts() {
		wp_register_script( 'table-maker-front', $this->base_url . '/js/table-maker-front.js', array('jquery'), $this->version, true );
		wp_register_script( 'jquery-stacktable', $this->base_url . '/js/stacktable.js', array('jquery'), '0.1.0', true );
			
	}

	public function wpsm_table_frontend_styles() {
		wp_enqueue_style( "wpsm-comptable-styles", plugins_url( "css/style.css" , dirname(__FILE__) ), null, $this->version, "all" );			
	}	

	public function backend_enqueue($hook) {
		if( $this->page_hook != $hook )
			return;
		wp_enqueue_style('wpsm-stylesheet', $this->base_url . '/css/table-maker.css', false, $this->version, 'all' );
		wp_enqueue_script('wpsm-comptable-script', $this->base_url . '/js/table-maker.js', array('jquery'), $this->version );
		wp_enqueue_script('jquery-ui-dialog' );
		wp_enqueue_script('jquery-effects-bounce');
		if (function_exists('wp_enqueue_media')) {
			wp_enqueue_media();
		}
		$wpsm_js_strings = array(
			'placeholder' 	=> __('Click to edit', 'wpsm-tableplugin'),
			'resize_error' 	=> __('Please enter valid numbers', 'wpsm-tableplugin'),
			'only_one' 	=> __('Please fill only one field', 'wpsm-tableplugin'),
			'insert_error_row' 	=> __('Please specify number less than existing rows count', 'wpsm-tableplugin'),
			'insert_error_col' 	=> __('Please specify number less than existing cols count', 'wpsm-tableplugin'),
			'switch_error' 	=> __('Please enter valid numbers between 1 and', 'wpsm-tableplugin')
		);
		wp_localize_script( 'wpsm-comptable-script', 'wpsm_js_strings', $wpsm_js_strings );
	}

	public function print_page() {
	?>
		<div class="wrap">
			<?php
				if(isset($_GET['action']) && $_GET['action'] == 'add'){
					printf('<h2>%s <a class="add-new-h2" href="%s">%s</a></h2>', __('Add Table', 'wpsm-tableplugin'), wp_nonce_url(admin_url('admin.php?page='.$this->page_slug), 'add_table'), __('View All', 'wpsm-tableplugin'));
					$this->create_ui();
				} elseif(isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['table']) && is_numeric($_GET['table'])) {
					printf( '<h2>%s <a class="add-new-h2" href="%s">%s</a></h2>', __('Edit Table', 'wpsm-tableplugin'), admin_url('admin.php?page='.$this->page_slug), __('View All', 'wpsm-tableplugin') );
					$table = $this->db->get($_GET['table']);
					if($table)
						$this->create_ui($table);
				} else {
					printf( '<h2>%s <a class="add-new-h2" href="%s">%s</a></h2>', __('Tables', 'wpsm-tableplugin'), wp_nonce_url(admin_url('admin.php?page='.$this->page_slug.'&action=add'), 'add_table'), __('Add New', 'wpsm-tableplugin') );
					$list_table = new WPSM_List_Table();
					$list_table->show();
				}
			?>
		</div>
	<?php
	}

	private function create_ui($table = false){
		$table_id = $table ? $table['id'] : '';
		$name = $table ? $table['tname'] : '';
		$rows = $table ? $table['trows'] : 4;
		$cols = $table ? $table['tcols'] : 4;
		$subs = $table ? $table['subs'] : '';
		$color = $table ? $table['color'] : 'default';
		$responsive = $table ? $table['responsive'] : '';
		$curr_values = $table ? $table['tvalues'] : '';
		$col_span = $cols; 
		$sub_array = explode(',', $subs); 
		?>
			<form autocomplete="off" method="POST" class="wpsm-form">
				<input type="text" class="wpsm-comptable-title" placeholder="<?php _e('Table Name', 'wpsm-tableplugin'); ?>" name="table_name" value="<?php echo esc_attr($name); ?>"  required="required" />
				<input type="hidden" class="wpsm-rows" value="<?php echo esc_attr($rows); ?>" name="table_rows" />
				<input type="hidden" class="wpsm-cols" value="<?php echo esc_attr($cols); ?>" name="table_cols" />
				<input type="hidden" class="wpsm-subs" value="<?php echo esc_attr($subs); ?>" name="table_subs" />
				<div class="wpsm-options">
					<p class="description">
						<select name="table_respon" id="wpsm_respon">
						    <option value="0" <?php selected( $responsive, 0 ); ?>><?php _e('No', 'wpsm-tableplugin') ?></option>
    						<option value="1" <?php selected( $responsive, 1 ); ?>><?php _e('Table row stack', 'wpsm-tableplugin') ?></option>
    						<option value="2" <?php selected( $responsive, 2 ); ?>><?php _e('Table column stack', 'wpsm-tableplugin') ?></option>
						</select>
						<label for="wpsm_respon"> - <?php _e('enable responsive stack style for mobile devices','wpsm-tableplugin'); ?></label>					
					</p>
					<div class="wpsm-controls">
						<button id="wpsm-comptable-resize-btn" type="button" class="button"><?php _e('Resize Table', 'wpsm-tableplugin') ?></button>
						<button id="wpsm-row-switcher-btn" type="button" class="button"><?php _e('Switch Rows', 'wpsm-tableplugin') ?></button>
						<button id="wpsm-col-switcher-btn" type="button" class="button"><?php _e('Switch Cols', 'wpsm-tableplugin') ?></button>
						<button id="wpsm-comptable-addnew-btn" type="button" class="button"><?php _e('Add Inside', 'wpsm-tableplugin') ?></button>
						<button id="wpsm-comptable-remove-btn" type="button" class="button"><?php _e('Remove', 'wpsm-tableplugin') ?></button>
						<select id="wpsm-colors" name="table_color" class="wpsm-select-color">
							<option value="default" <?php if($color == 'default') echo 'selected'; ?>><?php _e('Grey', 'wpsm-tableplugin') ;?></option>
							<option value="black" <?php if($color == 'black') echo 'selected'; ?>><?php _e('Black', 'wpsm-tableplugin') ;?></option>
							<option value="yellow" <?php if($color == 'yellow') echo 'selected'; ?>><?php _e('Yellow', 'wpsm-tableplugin') ;?></option>
							<option value="blue" <?php if($color == 'blue') echo 'selected'; ?>><?php _e('Blue', 'wpsm-tableplugin') ;?></option>
							<option value="red" <?php if($color == 'red') echo 'selected'; ?>><?php _e('Red', 'wpsm-tableplugin') ;?></option>
							<option value="green" <?php if($color == 'green') echo 'selected'; ?>><?php _e('Green', 'wpsm-tableplugin') ;?></option>
							<option value="orange" <?php if($color == 'orange') echo 'selected'; ?>><?php _e('Orange', 'wpsm-tableplugin') ;?></option>
							<option value="purple" <?php if($color == 'purple') echo 'selected'; ?>><?php _e('Purple', 'wpsm-tableplugin') ;?></option>
						</select>
						<?php if($table) submit_button( __('Save Changes', 'wpsm-plugin'), 'primary right', 'wpsm-save-changes', false ); ?>
					</div>					
				</div>
				<div class="wpsm_comptable_admin_description">
					<div class="wpsm_comptable_shortcode_echo">
						[wpsm_comparison_table id="<?php echo $table_id; ?>" class=""]
					</div>
					<div class="wpsm_comptable_shortcode_hidden">
						[wpsm_comparison_table id="<?php echo $table_id; ?>" class="<span id='wpsm_comp_shortcode_firsthover'></span><span id='wpsm_comp_shortcode_calign'></span>"]
					</div>					
					<p class="description">
						<input type="checkbox" id="wpsm_first_col_hover_check" /><label for="wpsm_first_col_hover_check"> - <?php _e('Add "mark the first column" class to shortcode', 'wpsm-tableplugin'); ?></label>&nbsp;&nbsp;
						<input type="checkbox" id="wpsm_calign_check" /><label for="wpsm_calign_check"> - <?php _e('Add "center text align" class to shortcode', 'wpsm-tableplugin'); ?></label>
					</p>
				</div>
				<table class="wpsm-comptable">
					<thead class="wpsm-thead">
						<tr>						
							<?php for ($j=1; $j <= $cols; $j++): ?>
								<th><input placeholder="<?php _e('Click to edit', 'wpsm-tableplugin') ?>" type="text" name="table_values[0][<?php echo $j; ?>]" value="<?php echo isset($curr_values[0][$j]) ? esc_attr($curr_values[0][$j]) : ''; ?>" /></th>
							<?php endfor; ?>
						</tr>
					</thead>
					<tbody class="wpsm-tbody">
					<?php for ($i=1; $i <= $rows; $i++): ?>
						<?php echo in_array($i, $sub_array) ? '<tr class="subheader">' : '<tr>'; ?>
						<?php for ($j=1; $j <= $cols; $j++): ?>
							<?php echo in_array($i, $sub_array) ? '<td colspan="'.$col_span.'">' : '<td>'; ?>
							<?php if ($j==1) {echo '<span class="num_row_wpsm_table">'.$i.'</span>' ;} ;?>
								<textarea placeholder="<?php _e('Click to edit', 'wpsm-tableplugin') ?>" type="text" name="table_values[<?php echo $i; ?>][<?php echo $j; ?>]" ><?php echo isset($curr_values[$i][$j]) ? esc_attr($curr_values[$i][$j]) : ''; ?></textarea>

							</td>
							<?php if(in_array($i, $sub_array)) break; ?>
						<?php endfor; ?>
						</tr>
					<?php endfor; ?>
					</tbody>
				</table>
				<div class="wpsm_comptable_description">
					<span class="wpsm_comptable_info_span">&#8505;</span>
					<?php _e('Each cell supports html and shortcodes. ', 'wpsm-tableplugin'); ?>
					<br/>				
					<?php $placeholders = array('tick', 'cross', 'info', 'warning', 'heart', 'lock', 'star', 'star-empty'); ?>
					<?php _e('To add icons you can use these placeholders: ', 'wpsm-tableplugin'); foreach($placeholders as $p){ echo "<strong>%".strtoupper($p)."%</strong>&nbsp;&nbsp;&nbsp;"; } ?>
					<?php echo '<br/><br/>'; echo ' <strong>%COL-CHOICE% &nbsp;&nbsp; %COL-CHOICE-IMAGE% &nbsp;&nbsp; %ROW-CHOICE% &nbsp;&nbsp; %ROW-CHOICE-IMAGE%</strong>'; _e(' makes row or col as featured.', 'wpsm-tableplugin'); ?>
					<?php if( function_exists('wpsm_shortcode_button') ) {
						echo '<br/><br/>';
						_e('Useful shortcodes:', 'wpsm-tableplugin');
						echo '<br/><br/>';
						echo '<strong>[wpsm_button color="green" size="big" link="" icon="" target="_blank" rel="nofollow"]Button[/wpsm_button]</strong><br/>';
						_e('Possible color attribute:', 'wpsm-tableplugin'); echo ' orange, blue, green, black, rosy, brown, pink, purple, gold, teal. <br/>'; _e('Possible sizes:', 'wpsm-tableplugin'); echo ' small, medium, big, giant. <br/>'; _e('Possible icons:', 'wpsm-tableplugin'); echo ' download, check-circle, link, map-marker, star, heart, save, check-circle. ';
						echo '<br/><br/>';
						echo '<strong>[wpsm_numcircle num="1" style="2"]</strong><br/>';
						_e('Nembered circle. Place number in', 'wpsm-tableplugin'); echo ' num';
						echo '<br/><br/>';
						echo '<strong>[wpsm_highlight color="yellow"]Content[/wpsm_highlight]</strong><br/>';
						_e('Possible colors: ', 'wpsm-tableplugin'); echo ' yellow, blue, red, green, black';
						echo '<br/><br/>';
						echo '<strong>[wpsm_bar title="Design" percentage="60" color="#fb7203"]</strong><br/>';
						_e('Animated bar. You can use any color', 'wpsm-tableplugin');	
						echo '<br/><br/>';	
						echo '<strong>[wpsm_is_user]Content[/wpsm_is_user]</strong><br/>';
						_e('Shows content only for logged users', 'wpsm-tableplugin');	
						echo '<br/><br/>';	
						echo '<strong>[wpsm_is_guest]Content[/wpsm_is_guest]</strong><br/>';
						_e('Shows content only for not logged users', 'wpsm-tableplugin');																

					}
					?>
				</div>				
				<div class="wpsm_table_helper_image">
					<img src="" class="wpsm_table_helper_preview_image" alt="" style="max-width: 80px" width="80" height="80" />
					<strong><?php _e('Image Helper', 'wpsm-tableplugin'); ?></strong>
					<p class="description"><?php _e('Upload or choose image here and copy code to table', 'wpsm-tableplugin'); ?></p>
					<input type="text" size="70" class="wpsm_table_helper_upload_image" value="" />									
					<a href="#" class="wpsm_table_helper_upload_image_button button" rel=""><?php _e('Choose Image', 'wpsm-tableplugin'); ?></a>
					<small>&nbsp;<a href="#" class="wpsm_table_helper_clear_image_button button">X</a></small>
					<br /><br />
					
				</div>				

				<div class="clear"></div>								
				<?php
					if($table) {
						echo '<p class="submit">';
							submit_button( __('Save Changes', 'wpsm-plugin'), 'primary', 'wpsm-save-changes', false );
							echo ' ';
							submit_button( __('Dublicate Table', 'wpsm-plugin'), 'primary', 'wpsm-create-table', false );
							echo ' ';
							submit_button( __('Export XML', 'wpsm-plugin'), 'primary',  'wpsm-export-table', false );
						echo '</p>';
					} else {
						submit_button( __('Create Table', 'wpsm-plugin'), 'primary', 'wpsm-create-table', true );
					}
				?>
			</form>

			<?php if(!$table) : ?>
			<form enctype="multipart/form-data" method="POST">
				<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
				<p class="submit">
				<input name="upload_file" type="file" />
					<?php submit_button( __('Import XML', 'wpsm-plugin'), 'primary',  'wpsm-import-table', false ); echo ' '; ?>
					<?php submit_button( __('Import CSV', 'wpsm-plugin'), 'primary',  'wpsm-import-csv', false ); echo ' '; ?>
					<select id="wpsm-delimiter" name="csv_delimiter" class="wpsm-select-delimiter">
						<option value=","><?php _e('"," (comma)', 'wpsm-tableplugin') ;?></option>
						<option value=";"><?php _e('";" (semi-colon)', 'wpsm-tableplugin') ;?></option>
					</select>
				</p>
			</form>			
			<?php endif; ?>				
			
			<div id="wpsm-comptable-resize-dialog" class="wpsm-dialog" title="<?php _e('Change Table Size', 'wpsm-tableplugin') ?>">
				<div class="wpsm-dialog-error"></div>
				<?php _e('Cols', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-col-count" />
				<?php _e('Rows', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-row-count" />
				<?php _e('Sub-header Rows (e.g.1,3,6)', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-sub-array" />
				<button class="button button-primary"><?php _e('Apply', 'wpsm-tableplugin') ?></button>
			</div>

			<div id="wpsm-row-switcher-dialog" class="wpsm-dialog" title="Switch Rows">
				<div class="wpsm-dialog-error"></div>
				<?php _e('Row 1', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-row1" />
				<?php _e('Row 2', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-row2" />
				<button class="button button-primary"><?php _e('Switch', 'wpsm-tableplugin') ?></button>
			</div>

			<div id="wpsm-col-switcher-dialog" class="wpsm-dialog" title="Switch Columns">
				<div class="wpsm-dialog-error"></div>
				<?php _e('Col 1', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-col1" />
				<?php _e('Col 2', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-col2" />
				<button class="button button-primary"><?php _e('Switch', 'wpsm-tableplugin') ?></button>
			</div>
			
			<div id="wpsm-comptable-addnew-dialog" class="wpsm-dialog" title="<?php _e('Add Empty Row/Column', 'wpsm-tableplugin') ?>">
				<div class="wpsm-dialog-error"></div>
				<?php _e('Add empty col after (number)', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-col-after" />
				<?php _e('Add empty row after (number)', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-row-after" />
				<button class="button button-primary"><?php _e('Apply', 'wpsm-tableplugin') ?></button>
			</div>

			<div id="wpsm-comptable-remove-dialog" class="wpsm-dialog" title="<?php _e('Delete Row/Column', 'wpsm-tableplugin') ?>">
				<div class="wpsm-dialog-error"></div>
				<?php _e('Remove col (number)', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-col-remove" />
				<?php _e('Remove row (number)', 'wpsm-tableplugin') ?>: <input type="text" class="wpsm-row-remove" />
				<button class="button button-primary"><?php _e('Apply', 'wpsm-tableplugin') ?></button>
			</div>			

		<?php
	}

	private function is_plugin_page() {
		if( !is_admin() || !isset($_GET['page']) || $this->page_slug != $_GET['page'] || (!isset($_GET['action']) && !isset($_GET['action2'])) )
			return false;
		return true;
	}

	public function handle_requests($current_screen) {
		$user_caps = apply_filters('wpsmt_user_cappabilities', $this->user_caps);
		if(current_user_can($user_caps) && $current_screen->base == 'toplevel_page_wpsm_table_maker') { //Check if user have enough rights (min. author role by default)
			if(!$this->is_plugin_page())
				return;

			if(isset($_GET['action2']) && $_GET['action2'] != -1 && $_GET['action'] == -1)
				$_GET['action'] = $_GET['action2'];
			//Add a new table from the table list
			if($_GET['action'] == 'add' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'add_table') && isset($_POST['wpsm-create-table'])){
				if (!isset ($_POST['table_respon'])) {$_POST['table_respon'] = '';}
				$result = $this->db->add( $_POST['table_name'], $_POST['table_rows'], $_POST['table_cols'],  $_POST['table_subs'], $_POST['table_color'], $_POST['table_respon'], $_POST['table_values'] );
				if($result){
					$sendback = add_query_arg( array( 'page' => $_GET['page'], 'action' => 'edit', 'table' => $result, 'added' => true ), '' );
					wp_redirect(wp_nonce_url($sendback, 'edit_table'));
				}
			}
			//Edit the current table
			if($_GET['action'] == 'edit' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'edit_table') && isset($_POST['wpsm-save-changes'])){
				if (!isset ($_POST['table_respon'])) {$_POST['table_respon'] = '';}
				$result = $this->db->update( $_GET['table'], $_POST['table_name'], $_POST['table_rows'], $_POST['table_cols'], $_POST['table_subs'], $_POST['table_color'], $_POST['table_respon'], $_POST['table_values'] );
				$sendback = add_query_arg( array( 'page' => $_GET['page'], 'action' => 'edit', 'table' => $_GET['table'], 'updated' => $result ), '' );
			}
			//Dublicate the current table
			if($_GET['action'] == 'edit' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'edit_table') && isset($_POST['wpsm-create-table'])){
				if (!isset ($_POST['table_respon'])) {$_POST['table_respon'] = '';}
				$result = $this->db->add( $_POST['table_name'], $_POST['table_rows'], $_POST['table_cols'],  $_POST['table_subs'], $_POST['table_color'], $_POST['table_respon'], $_POST['table_values'] );
				if($result){
					$sendback = add_query_arg( array( 'page' => $_GET['page'], 'action' => 'edit', 'table' => $result, 'added' => true ), '' );
					wp_redirect(wp_nonce_url($sendback, 'edit_table'));
				}
			}
			//Single deleting in the table list
	 		if($_GET['action'] == 'delete' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_table') && isset($_GET['table']) ){
				if(is_numeric($_GET['table'])) {
					$result = $this->db->delete($_GET['table']);
					$sendback = add_query_arg( array( 'page' => $_GET['page'], 'deleted' => $result ), '' );
					wp_redirect($sendback);
				}
			} 
			//Bulk deleting in the table list
	 		if($_GET['action'] == 'delete' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'bulk-tables') && isset($_GET['table']) ){
				if(is_array($_GET['table'])) {
					$ids = $_GET['table'];
					$count = count($ids);
					for($i = 0; $i < $count; $i++) {
						$result = $this->db->delete($ids[$i]);
					}
					$sendback = add_query_arg( array( 'page' => $_GET['page'], 'deleted' => $result ), '' );
					wp_redirect($sendback);
				}
			}
			//Add table with importing XML file
			if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'add_table') && isset($_POST['wpsm-import-table'])) {
				if(is_uploaded_file($_FILES['upload_file']['tmp_name']) && $_FILES['upload_file']['type'] == 'text/xml') {
					$xml = simplexml_load_file($_FILES['upload_file']['tmp_name']);
					$array = xml2array($xml);
				}
				if(!empty($array)) {
					$array['subs'] = isset($array['subs']) ? $array['subs'] : '';
					$result = $this->db->add($array['tname'], $array['trows'], $array['tcols'], $array['subs'], $array['color'], $array['responsive'], $array['tvalues'] );
					$sendback = add_query_arg( array( 'page' => $_GET['page'], 'action' => 'edit', 'table' => $result, 'added' => true ), '' );
					wp_redirect($sendback);
				}
			}
			//Add table with importing CSV file
			if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'add_table') && isset($_POST['wpsm-import-csv'])) {
				if(is_uploaded_file($_FILES['upload_file']['tmp_name']) && $_FILES['upload_file']['type'] == 'text/csv' && isset($_POST['csv_delimiter'])) {
					if (($handle = fopen($_FILES['upload_file']['tmp_name'], "r")) !== FALSE) {
						$array =  csv2array( $handle, $_POST['csv_delimiter'] );
					fclose($handle); 
					}
				}
				if(!empty($array)) {
					$array['subs'] = isset($array['subs']) ? $array['subs'] : '';
					$result = $this->db->add(__('Noname Table', 'wpsm-tableplugin'), $array['trows'], $array['tcols'], $array['subs'], 'default', '0', $array['tvalues'] );
					$sendback = add_query_arg( array( 'page' => $_GET['page'], 'action' => 'edit', 'table' => $result, 'added' => true ), '' );
					wp_redirect($sendback);
				}
			}
			//Export the current table in XML file
			if($_GET['action'] == 'edit' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'edit_table') && isset($_POST['wpsm-export-table'])) {
				$result = $this->db->get($_GET['table']);
				if($result) {
					$this->xml_download($result);
				}
			}
		}

	}

	public function admin_notices(){
		if( !$this->is_plugin_page() )
			return;
		$warning = '<div class="notice notice-warning"><p>%s</p></div>';
		$success = '<div class="notice notice-success"><p>%s</p></div>';
		if(isset($_FILES['upload_file'])){
			if(!is_uploaded_file($_FILES['upload_file']['tmp_name']) || !isset($_POST['csv_delimiter'])) {
				if(!isset($_FILES['userfile']['name']) || $_FILES['upload_file']['type'] != 'text/xml' || $_FILES['upload_file']['type'] != 'text/csv') {
					echo sprintf($warning, __('Can not open file!', 'wpsm-tableplugin') );
				}
			}
		}
		if(isset($_GET['added']) && $_GET['added']):
			echo sprintf($success, __('The table has been created successfully!', 'wpsm-tableplugin') );
		elseif(isset($_GET['updated']) && $_GET['updated']):
			echo sprintf($success, __('The table has been updated successfully!', 'wpsm-tableplugin') );
		elseif(isset($_GET['deleted']) && $_GET['deleted']):
			echo sprintf($success, __('The table has been deleted successfully!', 'wpsm-tableplugin') );
		endif;
	}
	
	private function xml_download($result) {
		$converter = new Array_XML();
		$xmlStr = $converter->convert($result);
		header("Content-type: txt/xml",true,200);
		header("Content-Disposition: attachment; filename=" . $_POST['table_name'] . ".xml" );
		header("Pragma: no-cache");
		header("Expires: 0");
		echo $xmlStr;
		exit();
	}

	function comparison_table_callback( $atts ){

		$atts = shortcode_atts( 
			array( 
				'id' => false, 
				'color' => '',
				'class' => ''
			), $atts );

		if(!$atts['id']){
			_e("Please specify the table ID", 'wpsm-tableplugin');
			return;
		}
		
		$table = $this->db->get($atts['id']);
		
		if(!$table)
			return;

		ob_start();
		wp_enqueue_script('table-maker-front');
		wp_enqueue_script('jquery-columnhover');
		?>
			<?php if($table['responsive']) wp_enqueue_script('jquery-stacktable'); ?>
			<div class="wpsm-comptable-wrap">
				<table id="wpsm-table-<?php echo $atts['id'];?>" class="wpsm-comptable<?php echo ' '. $atts['class'].'' ; ?><?php if($table['responsive'] == 1) echo ' wpsm-comptable-responsive'; elseif($table['responsive'] == 2) echo ' wpsmt-column-stack'; ?>">
				<?php $change_color = ($atts['color']) ? $atts['color'] : $table['color'] ; ?>
					<thead class="wpsm-thead<?php echo ' wpsm-thead-'. $change_color; ?>">
						<tr>							
							<?php for ($j=1; $j <= $table['tcols']; $j++): ?>
								<?php if ($j==1 && empty($table['tvalues'][0][1])) :?>
									<th class="placeholder wpsm-placeholder"></th>
								<?php else :?>
									<th><?php echo $this->replace_placeholders($table['tvalues'][0][$j]); ?></th>
								<?php endif;?>	
								
							<?php endfor; ?>
						</tr>
					</thead>
					<tbody class="wpsm-tbody">
					<?php for($i=1; $i <= $table['trows']; $i++): ?>
					<?php $sub_array = explode(',', $table['subs']);  ?>
						<?php echo in_array($i, $sub_array) ? '<tr class="subheader">' : '<tr>'; ?>
							<?php for ($j=1; $j <= $table['tcols']; $j++): ?>
								<?php echo in_array($i, $sub_array) ? '<td colspan="'.$table['tcols'].'">' : '<td>'; ?>
								<?php if (!empty ($table['tvalues'][$i][$j])):?>
									<?php $table_cell_echo = $this->replace_placeholders($table['tvalues'][$i][$j]); ?>
									<?php echo do_shortcode($table_cell_echo); ?>
								<?php endif;?>
								</td>
								<?php if(in_array($i, $sub_array)) break; ?>
							<?php endfor; ?>
						</tr>
					<?php endfor; ?>
					</tbody>
				</table>
			</div>
		<?php
		return ob_get_clean();
	}

	public function replace_placeholders($str){
		$values 			= array();
		$values['tick'] 	= '<i class="wpsm-table-icon wpsm-icon-tick"></i>';
		$values['cross'] 	= '<i class="wpsm-table-icon wpsm-icon-cross"></i>';
		$values['info'] 	= '<i class="wpsm-table-icon wpsm-icon-info"></i>';
		$values['warning'] 	= '<i class="wpsm-table-icon wpsm-icon-warning"></i>';
		$values['heart'] 	= '<i class="wpsm-table-icon wpsm-icon-heart"></i>';
		$values['lock'] 	= '<i class="wpsm-table-icon wpsm-icon-lock"></i>';
		$values['star'] 	= '<i class="wpsm-table-icon wpsm-icon-star"></i>';
		$values['star-empty'] = '<i class="wpsm-table-icon wpsm-icon-star-empty"></i>';
		$values['col-choice'] = '<span class="badge_div_col"></span>';
		$values['col-choice-image'] = '<span class="badge_div_col badge_div_col_img"></span>';	
		$values['row-choice'] = '<span class="badge_div_row"></span>';
		$values['row-choice-image'] = '<span class="badge_div_row badge_div_col_img"></span>';			

		foreach ($values as $key => $value) {
			$str = str_replace('%'.strtoupper($key).'%', $value, $str);
		}
		return $str;
	}

	public function initialize()
	{
		$this->db->create_table();
	}

	public function rollback()
	{
		$table = WPSM_DB_Table::get_instance();
		$table->drop_table();
	}
}