<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPSM_List_Table extends WP_List_Table {

	private $db;

    public function __construct(){

    	$this->load_dependencies();

    	$this->db = WPSM_DB_Table::get_instance();

    	global $status, $page;

		parent::__construct( array(
			'singular'  => 'table',
			'plural'    => 'tables',
			'ajax'      => false,
			'screen'    => $_REQUEST['page']
		) );
    }

    function load_dependencies(){
    	require_once( 'class-wpsm-db-table.php' );
    }

	function get_columns(){
		$columns = array(
			'cb'	=> '<input type="checkbox" />',
			'id'	=> __('ID', 'wpsm-tableplugin'),
			'name'	=> __('Name', 'wpsm-tableplugin'),
			'trows'	=> __('Rows', 'wpsm-tableplugin'),
			'tcols'	=> __('Columns', 'wpsm-tableplugin'),
			'subs'	=> __('Sub-headers', 'wpsm-tableplugin'),
			'color'	=> __('Color','wpsm-tableplugin'),
			'responsive' => __('Responsive','wpsm-tableplugin')
		);
		return $columns;
	}

    function column_default($item, $column_name){
        return stripslashes($item[$column_name]);
    }

	function column_name($item){
		$actions = array(
			'edit' => sprintf('<a href="%s">%s</a>', wp_nonce_url(admin_url('admin.php?page='.$_REQUEST['page'].'&action=edit&table='.$item['id']), 'edit_table'), __('Edit', 'wpsm-tableplugin') ),
			'delete' => sprintf('<a href="%s">%s</a>', wp_nonce_url(admin_url('admin.php?page='.$_REQUEST['page'].'&action=delete&table='.$item['id']), 'delete_table'), __('Delete', 'wpsm-tableplugin') )
		);

		//Return the title contents
		return sprintf('%1$s %2$s',
			/*$1%s*/ stripslashes($item['tname']),
			/*$2%s*/ $this->row_actions($actions)
		);
	}

	function column_cb($item){
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
		);
	}

    function get_bulk_actions() {
        $actions = array(
            'delete'    => __('Delete', 'wpsm-tableplugin')
        );
        return $actions;
    }

	function prepare_items() {
		$per_page		= 25;
		$hidden			= array();
		$columns		= $this->get_columns();
		$sortable			= array();
		$curr_page		= $this->get_pagenum();
		$total_items	= $this->db->get_count();
		$data               = $this->db->get_page_items($curr_page, $per_page);
		$this->items	= $data;
		$this->_column_headers  = array($columns, $hidden, $sortable);
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items/$per_page)
		) );
	}

	function show(){
		?>
		<form method="GET">
			<input type="hidden" name="page" value="<?php echo $_GET['page'] ?>">
			<?php
				$this->prepare_items();
				$this->display();
			?>
		</form>
		<?php
	}
}