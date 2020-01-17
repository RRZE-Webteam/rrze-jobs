function createBlock() {
	const {registerBlockType} = wp.blocks;
	const {createElement} = wp.element;
	const {InspectorControls} = wp.editor;
	const {TextControl, SelectControl, ToggleControl} = wp.components;

	registerBlockType( phpConfig.block.name, {
		title: phpConfig.block.title,
		category: phpConfig.block.category,
		icon: phpConfig.block.icon,
		edit(props){
			const att = props.attributes;
			const setAtts = props.setAttributes;

			function changeField( val ){
				setAtts( {[this]: val} );
			}	

			var ret = [];
			for ( var key in phpConfig){
				switch( this.phpConfig[key]['field_type'] ){
					case 'text': 
						ret.push( createElement( TextControl, { value: att[key], label: this.phpConfig[key]['label'], type: 'text', onChange: changeField.bind( key ) } ) );
						break;
					case 'select': 
						var opts = [];
						for ( var v in this.phpConfig[key]['values'] ){
							opts.push( JSON.parse( '{"value":"' + v + '", "label":"' + this.phpConfig[key]['values'][v] + '"}' ) );
						}
						ret.push( createElement( SelectControl, { value: att[key], label: this.phpConfig[key]['label'], type: 'text', onChange: changeField.bind( key ), options: opts } ) );
						break;
					case 'toggle': 
						ret.push( createElement( ToggleControl, { checked: att[key], label: this.phpConfig[key]['label'], type: 'text', onChange: changeField.bind( key ) } ) );
						break;
				}
			}

			return createElement('div', {}, [
				createElement( 'div', {}, phpConfig.block.message ),
				createElement( InspectorControls, {}, ret )
			] )
		},
		save(){
			 return null;
		}	
	} );
}

createBlock();