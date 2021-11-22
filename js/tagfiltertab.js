Vue.component('tab-tags', {
    props: ['saved', 'errors', 'formdata', 'schema', 'userroles'],
    template: '<section><form>' + 
	        '<div v-for="(field, index) in schema.fields">' +
	            '<fieldset v-if="field.type == \'fieldset\'" class="fs-formbuilder"><legend>{{field.legend}}</legend>' + 
	                '<component v-for="(subfield, index) in field.fields "' +
	                    ':key="index"' +
	                    ':is="selectComponent(subfield)"' +
	                    ':errors="errors"' +
	                    ':name="index"' +
	                    ':userroles="userroles"' +
	                    'v-model="formdata[index]"' + 
	                    'v-bind="subfield">' +
	                '</component>' + 
	            '</fieldset>' +
	            '<component v-else' +
	                ':key="index"' +
	                ':is="selectComponent(field)"' +
	                ':errors="errors"' +
	                ':name="index"' +
	                ':userroles="userroles"' +
	                'v-model="formdata[index]"' +
	                'v-bind="field">' +
	            '</component>' + 
	        '</div>' +
	        '<div v-if="saved" class="metasubmit"><div class="metaSuccess">Saved successfully</div></div>' +
	        '<div v-if="errors" class="metasubmit"><div class="metaErrors">Please correct the errors above</div></div>' +
	        '<div class="metasubmit"><input type="submit" @click.prevent="saveInput" value="save"></input></div>' +
    	'</form></section>',
	mounted: function(){
		FormBus.$on('forminput', formdata => {

			var thumb = this.schema.fields.language.options[formdata.value];
			if(formdata.name == "language")
			{
				FormBus.$emit('forminput', {'name': 'lang', 'value' : formdata.value });
				FormBus.$emit('forminput', {'name': 'thumb', 'value' : thumb.toUpperCase() });
			}

		});
	},
	methods: {
		selectComponent: function(field)
		{
			return 'component-'+field.type;
		},
		saveInput: function()
		{
  			this.$emit('saveform');
		},
		getFieldClass: function(field)
		{
			if(field.type == 'fieldset')
			{ 
				return; 
			}
			else if(field.class === undefined )
			{
				return 'large';
			}
			else
			{
				var fieldclass = field.class;
				delete field.class;
				return fieldclass;
			}
		},		
	}
})
