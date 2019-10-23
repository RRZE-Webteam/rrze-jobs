function dotheblock() {
	const {registerBlockType} = wp.blocks; //Blocks API
	const {createElement} = wp.element; //React.createElement
	const {__} = wp.i18n; //translation functions
	const {InspectorControls} = wp.editor; //Block inspector wrapper
	const {TextControl, SelectControl, ToggleControl} = wp.components; //Block inspector wrapper

	registerBlockType( 'rrze-jobs/jobs', {
		title: __( 'RRZE Jobs', 'rrze-jobs' ),
		category: 'widgets',
		icon: 'admin-users',
		edit(props){
			const attributes =  props.attributes;
			const setAttributes =  props.setAttributes;

			function changeField(val){
				setAttributes({[this]: val});
			}	

			function createTexts( fields ){
				var aLength = fields.length;
				var ret = [];
			
				for (var i = 0; i < aLength; i++) {
					var parts = fields[i].split('|');
					ret.push( createElement( TextControl, { value: eval( 'attributes.' + parts[0] ), label: __( parts[1], 'rrze-jobs' ), type: 'text', onChange: changeField.bind( parts[0] ) } ) );
				}
				return ret;
			}	
			
			function createToggles( fields ){
				var aLength = fields.length;
				var ret = [];
			
				for (var i = 0; i < aLength; i++) {
					var parts = fields[i].split('|');
					ret.push( createElement( ToggleControl, { checked: eval( 'attributes.' + parts[0] ), label: __( parts[1], 'rrze-jobs' ), onChange: changeField.bind( parts[0] ) } ) );
				}
				return ret;
			}		

			function createSelects( fields ){
				var ret = [];
				var xL = fields.length;

				for (var x = 0; x < xL; x++) {
					var tmp = fields[x].split(':');
					var parts = tmp[0].split('|');
					var field = parts[0];
					var fieldlabel = parts[1];
					
					var opt = tmp[1].split(',');
					var yL = opt.length;
					var opts = '';

					for (var y = 0; y < yL; y++) {
						parts = opt[y].split('|');
						opts += "{value:'" + parts[0] + "', label: __('" + parts[1] + "', 'rrze-downloads')},";
					}
					opts = opts.substring(0, opts.length - 1 );
					ret.push( createElement( SelectControl, { value: eval( 'attributes.' + field ), label: __( fieldlabel, 'rrze-downloads' ), onChange: changeField.bind( field ),  options: eval('[' + opts +']') } ) );
				}
				return ret;
			}

			var providerSelect = createSelects( ['provider|Provider:all|Alle,interamt|Interamt,univis|UnivIS'] );
			var elementsText = createTexts( ['department|Department(s)', 'jobtype|Job Type', 'jobid|Job ID'] );
			var elementsSelect = createSelects( ['orderby|Sortierung nach:title|Titel,date|Datum','sort|Sortierreihenfolge:ASC|A-Z,DESC|Z-A'] );

			return createElement('div', {}, [
				createElement( 'div', {}, __( 'Klicken Sie hier, um die Einstellungen auf der rechten Seite vorzunehmen.', 'rrze-jobs') ),
				createElement( InspectorControls, {},
					[
						providerSelect,
						elementsText,
						elementsSelect
					]
				)
			] )
		},
		save(){
			 return null; //save has to exist. This all we need
		}	
	} );
}

dotheblock();