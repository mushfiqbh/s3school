<?php 
/**
 * Template Name: Admin Group
 */
global $wpdb;

/*=================	
	Add Group
=================*/
if (isset($_POST['addGroup'])) {
  $insert = $wpdb->insert('ct_group', array(
    'groupName' => $_POST['groupName'],
    'groupNote' => $_POST['groupNote']
  ));
  $message = ms3message($insert, 'Added');
}


/*=================	
	Update Group
=================*/
if (isset($_POST['updateGroup'])) {
  $update = $wpdb->update('ct_group', array(
    'groupName' => $_POST['groupName'],
    'groupNote' => $_POST['groupNote']
  ), array(
    'groupId' => $_POST['id']
  ));
  $message = ms3message($update, 'Updated');
}


/*=================	
	Delete Group
==================*/
if (isset($_POST['deleteGroup'])) {
  $delete = $wpdb->delete('ct_group', array( 'groupId' => $_POST['id'] ));
  $message = ms3message($delete, 'Deleted');
}


/*=================	
	Edit Group
==================*/
$editid = 0;
if (isset($_POST['editGroup'])) {
  $editid = $_POST['id'];
  $edit   = $wpdb->get_results("SELECT * FROM ct_group WHERE groupId = $editid");
  $edit   = $edit[0];
}
?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

  <div class="">
    <div class="container">
      <div class="row">
        <div class="col-md-12">


          <?php } ?>

            <div class="container-fluid maxAdminpages" style="padding-left: 0">	
              
              <!-- Show Status message -->
              <?php if(isset($message)){ ms3showMessage($message); } ?>
              
              <h2>Group Management
              </h2>
              <br>	
              <div class="row">		
                <div class="col-md-5">			
                  <div class="panel panel-info">			  
                    <div class="panel-heading">
                      <h3>
                        <?= isset($edit) ? 'Edit' : 'Add'; ?> Group<br><small>Add Group information</small>
                      </h3>
                    </div>			  
                    <div class="panel-body">			    
                      <form action="" method="POST">		    		
                        <input type="hidden" name="id" value="<?= $editid ?>">		    		
                        <div class="form-group ">			    		
                          <label>Group Name
                          </label>			    		
                          <input class="form-control" type="text" name="groupName" value="<?= isset($edit) ? $edit->groupName : ''; ?>" required>			    	
                        </div>			    	
                        <div class="form-group">			    		
                          <label>Note
                          </label>			    		
                          <textarea class="form-control" name="groupNote">
                            <?= isset($edit) ? $edit->groupNote : ''; ?>
                          </textarea>			    	
                        </div>			    	
                        <div class="form-group text-right">			    		
                          <button class="btn btn-primary" type="submit" name="<?= isset($edit) ? 'updateGroup' : 'addGroup'; ?>">
                            <?= isset($edit) ? 'Update' : 'Add'; ?> Group
                          </button>			    	
                        </div>			    
                      </form>			  
                    </div>			
                  </div>		
                </div>		
                <div class="col-md-7">			
                  <div class="panel panel-info">			  
                    <div class="panel-heading">
                      <h3>All Group<br><small>All Group information</small>
                      </h3>
                    </div>			  
                    <div class="panel-body">					
                      <div class="panel-group" id="accordion">						
                        <table class="table table-bordered table-responsive">							
                          <thead>								
                            <tr>									
                              <th>Name
                              </th>									
                              <th>Note
                              </th>									
                              <th>Action
                              </th>								
                            </tr>							
                          </thead>							
                          <tbody>							
                            <?php								
                            $groups = $wpdb->get_results( "SELECT * FROM ct_group" );
                            foreach ($groups as $group) {									?>									
                              <tr>										
                                <td><?= $group->groupName ?></td>										
                                <td><?= $group->groupNote ?></td>										
                                <td>											
                                  <form class="pull-right actionForm" method="POST" action="">							        	
                                    <input type="hidden" name="id" value="<?= $group->groupId ?>">							        	
                                    <button type="submit" name="editGroup" class="btn-link">							        		
                                      <span class="dashicons dashicons-welcome-write-blog">
                                      </span>
                                      </span>							        	
                                    </button>							        	
                                    <button type="button" class="btn-link btnDelete" data-id='<?= $group->groupId ?>'>							        		
                                      <span class="dashicons dashicons-trash">
                                      </span>							        	
                                    </button>							        
                                  </form>										
                                </td>									
                              </tr>									
                              <?php 
                            } ?>							
                          </tbody>						
                        </table>					
                      </div>			  
                    </div>			
                  </div>		
                </div>	
              </div>
            </div>

          <?php if ( ! is_admin() ) { ?>

  
        </div>
      </div>
    </div>
  </div>
</div>
<?php get_footer(); } ?>


<div id="deleteModal" class="modal fade" role="dialog">  
  <div class="modal-dialog modal-sm">    
    <!-- Modal content-->    
    <div class="modal-content">      
      <div class="modal-header">        
        <button type="button" class="close" data-dismiss="modal">&times;</button>        
        <h4 class="modal-title">Delete Data
        </h4>      
      </div>      
      <div class="modal-body">        
        <p class="text-danger">You can't recover the data after delete.
        </p>      
      </div>      
      <div class="modal-footer">	      
        <form action="" method="POST">	      	
          <input type="hidden" name="id" class="id">        	
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close
          </button>        	
          <button type="submit" class="btn btn-danger" name="deleteGroup">Delete
          </button>	      
        </form>      
      </div>    
    </div>  
  </div>
</div>


<script type="text/javascript">
  (function($) {
    $(document).ready(function() {
      $('.btnDelete').click(function(event) {
        $('#deleteModal').find('.id').val($(this).data('id'));
        $('#deleteModal').modal("show");
      });
    });
  })( jQuery );
</script>