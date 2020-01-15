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
						opts += "{value:'" + parts[0] + "', label: __('" + parts[1] + "', 'rrze-jobs')},";
					}
					opts = opts.substring(0, opts.length - 1 );
					ret.push( createElement( SelectControl, { value: eval( 'attributes.' + field ), label: __( fieldlabel, 'rrze-jobs' ), onChange: changeField.bind( field ),  options: eval('[' + opts +']') } ) );
				}
				return ret;
			}

			// alert(phpConfig.provider['values']); FUNKTIONIERT
			// alert(phpConfig.provider['field_type']);
			// for ( var key in phpConfig){
			// 	alert('key ist ' + key); FUNKTIONIERT
			// }
			// FAZIT: besser JSON verwenden

			var providerSelect = createSelects( ['provider|Provider:interamt|Interamt,univis|UnivIS'] );
			var elementsText = createTexts( ['orgids|OrgID(s)', 'jobid|Job ID'] );
			var elementsSelect = createSelects( ['internal|Interne Stellenanzeigen:exclude|nicht ausgeben,include|auch ausgeben,only|nur diese ausgeben'] );
			var limitText = createTexts( ['limit|Anzahl (leer = unbegrenzt)'] );
			var orderSelect = createSelects( ['orderby|Sortierung nach:job_title|Titel der Stellenanzeige,application_start|Öffentlicher Ausschreibung,application_end|Bewerbungsschluss,job_start|Beginn der Tätigkeit','order|Sortierreihenfolge:ASC|aufsteigend,DESC|absteigend'] );
			var fallbackText = createTexts( ['fallback_apply|Ersatz Adresse'] );

			return createElement('div', {}, [
				createElement( 'div', {}, __( 'Klicken Sie hier, um die Einstellungen auf der rechten Seite vorzunehmen.', 'rrze-jobs') ),
				createElement( InspectorControls, {},
					[
						providerSelect,
						elementsText,
						elementsSelect,
						limitText,
						orderSelect,
						fallbackText
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