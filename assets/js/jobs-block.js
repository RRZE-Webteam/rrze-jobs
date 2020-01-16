function createBlock() {
	const {registerBlockType} = wp.blocks; //Blocks API
	const {createElement} = wp.element; //React.createElement
	const {InspectorControls} = wp.editor; //Block inspector wrapper
	const {TextControl, SelectControl, ToggleControl} = wp.components; //Block inspector wrapper

	registerBlockType( 'rrze-jobs/jobs', {
		title: phpConfig.block.title,
		category: phpConfig.block.category,
		icon: phpConfig.block.icon,
		edit(props){
			const attributes =  props.attributes;
			const setAttributes =  props.setAttributes;

			function changeField(val){
				setAttributes({[this]: val});
			}	

			var ret = [];
			for ( var key in phpConfig){
				switch( eval( 'phpConfig.' + key + '[\'field_type\']') ){
					case 'text': 
						ret.push( createElement( TextControl, { value: eval( 'attributes.' + key ), label: eval( 'phpConfig.' + key + '[\'label\']'), type: 'text', onChange: changeField.bind( key ) } ) );
						break;
					case 'select': 
						var opts = '';
						var options = eval( 'phpConfig.' + key + '.values');
						for ( var det in options ){
							opts += "{value:'" + det + "', label: '" + eval( 'phpConfig.' + key + '.values.' + det ) + "'},";
						}
						opts = '[' + opts.substring(0, opts.length - 1 ) + ']';
						ret.push( createElement( SelectControl, { value: eval( 'attributes.' + key ), label: eval( 'phpConfig.' + key + '[\'label\']'), type: 'text', onChange: changeField.bind( key ), options: eval( opts ) } ) );
						break;
					case 'toggle': 
						ret.push( createElement( ToggleControl, { checked: eval( 'attributes.' + key ), label: eval( 'phpConfig.' + key + '[\'label\']'), type: 'text', onChange: changeField.bind( key ) } ) );
						break;
				}
			}

			return createElement('div', {}, [
				createElement( 'div', {}, phpConfig.block.message ),
				createElement( InspectorControls, {}, ret )
			] )
		},
		save(){
			 return null; //save has to exist. This all we need
		}	
	} );
}

createBlock();