<div class="swiftriver">

	<!-- tabs -->
	<div class="tabs">
		<a name="add"></a>
		<ul class="tabset">
			<li><a href="#" class="active"><?php echo Kohana::lang("ui_main.add_edit"); ?></a></li>
		</ul>

		<div class="tab" id="addedit" style="display:none;">
			<?php echo form::open(); ?>
			<?php echo form::close(); ?>
		</div>
	</div>
	<!-- /tabs -->

	<?php echo form::open(); ?>
	<table class="table client-list">
		<tr>
			<th class="col-1"><?php echo form::checkbox("all_clients"); ?></th>
			<th class="col-2"><?php echo Kohana::lang("swiftriver.client_name"); ?></th>
			<th class="col-3"><?php echo Kohana::lang("swiftriver.client_url"); ?></th>
			<th class="col-4"><?php echo Kohana::lang("ui_admin.actions"); ?></th>
		</tr>
		
		<tr class="no-display-data" style="display: none;">
			<td colspan="4" class="col">
				<h3><?php echo Kohana::lang("swiftriver.no_clients"); ?></h3>
			</td>
		</td>
	</table>
	<?php echo form::close(); ?>
</div>

<?php
	 // Backbone JS (+underscore)
	echo html::script(array(
	    'plugins/swiftriver/media/js/underscore-min',
	    'plugins/swiftriver/media/js/backbone-min'
	));
?>

<script type="text/template" id="client-item-template">
	<td class="col-1"><input type="checkbox" name="client_id"></td>
	<td class="col-2">
		<div class="post">
			<h4><%= client_name %></h4>
		</div>
	</td>
	<td class="col-3"><%= client_url %></td>
	<td class="col-4">
		<ul>
			<li class="none-separator"><a href="#" class="edit"><?php echo Kohana::lang("ui_main.edit"); ?></a></li>
			<li><a href="#" class="del"><?php echo Kohana::lang("ui_main.delete"); ?></a></li>
		</ul>
	</td>
</script>

<!-- template for the client authentication info -->
<script type="text/template" id="client-auth-details-template">
	<div class="tab_form_item">
		<strong><?php echo Kohana::lang("swiftriver.client_id"); ?></strong><br/>
		<span><%= client_id %></span>
	</div>
	<div style="clear:both;"></div>
	<div class="tab_form_item">
		<strong><?php echo Kohana::lang("swiftriver.client_secret"); ?></strong><br/>
		<span><%= client_secret %></span>
	</div>
	<div style="clear: both;"></div>
</script>

<!-- template for the client details -->
<script type="text/template" id="client-details-template">
	<div class="tab_form_item">
		<strong><?php echo Kohana::lang("swiftriver.client_name"); ?></strong><br/>
		<input type="text" name="client_name" value="<%= client_name%>" class="text" />
	</div>
	<div style="clear: both;"></div>
	<div class="tab_form_item">
		<strong><?php echo Kohana::lang("swiftriver.client_url"); ?></strong><br/>
		<input type="text" name="client_url" value="<%= client_url %>" class="text long" />
	</div>
	<div style="clear: both;"></div>
	<div class="tab_form_item">
		<input type="submit" class="save-rep-btn" value="<?php echo Kohana::lang('ui_main.save'); ?>"/>
	</div>
</script>


<script type="text/javascript">
	/**
	 * Backbone JS wiring for the clients listing
	 */
	$(function(){
	
		// Client model
		var Client = Backbone.Model.extend();
	
		// Collection of clients (client models)
		var ClientsList = Backbone.Collection.extend({
			model: Client,
			url: "<?php echo $action_url; ?>"
		});
	
		// Declare the client listing 
		var swiftriverClients = new ClientsList();
		
		// Client details form
		var ClientDetailsView = Backbone.View.extend({

			el: "#addedit form",
			
			template: _.template($("#client-details-template").html()),
			
			// Template to render the client id and client secret
			authTemplate: _.template($("#client-auth-details-template").html()),

			render: function() {
				var modelJSON = this.model.toJSON();
				if (modelJSON.id !== undefined || modelJSON.id !== null) {
					this.$el.append(this.authTemplate(modelJSON));
				}

				this.$el.append(this.template(modelJSON));
				
				return this;
			},
		});

		// Single client list item view
		var ClientItemView = Backbone.View.extend({

			tagName: "tr",
		
			template: _.template($("#client-item-template").html()),
			
			// Events
			events: {
				// Edit link clicked
				"click .col-4 a.edit": "edit",
			
				// Delete button clicked
				"click .col-4 a.del": "delete"
			},
		
			edit: function(e) {
				// Show add/edit form
				console.log("editing...");
				var view = new ClientDetailsView({model: this.model});
				
				$("div#addedit form").html(view.render().el);
				$("div#addedit").slideDown();

				return false;
			},
		
			delete: function(e) {
				var view = this;

				// Delete the client record
				this.model.destroy({
					wait: true,
					success: function(response) {
						view.remove();
						// Show message
					}
				});

				return false;
			},
		
			render: function() {
				this.$el.html(this.template(this.model.toJSON()));
				return this;
			}
		});

		// View for the clients listing
		var ClientListView = Backbone.View.extend({
			el: "div.swiftriver",
		
			initialize: function() {
				swiftriverClients.on("reset", this.addClients, this);
				swiftriverClients.on("add", this.addClient, this);

				// Checks if the clients list is empty
				swiftriverClients.on("reset", this.checkEmpty, this);
				swiftriverClients.on("add", this.checkEmpty, this);
			},
		
			// Adds a single client to the listing
			addClient: function(client) {
				var view = new ClientItemView({model: client});
				this.$("table.client-list").append(view.render().el);
			},
		
			// Iterates the clients list and adds each client to the list view
			addClients: function() {
				swiftriverClients.each(this.addClient, this);
			},
		
			checkEmpty: function() {
				if (swiftriverClients.length) {
					this.$(".no-display-data").hide();
				} else {
					this.$(".no-display-data").show();
				}
			}
		
		});
	
		// Bootstrap the clients listing
		var clientsView = new ClientListView();
		swiftriverClients.reset(<?php echo $clients; ?>);
	
	});
</script>