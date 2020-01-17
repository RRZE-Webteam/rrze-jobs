function createBlock() {
	const {registerBlockType} = wp.blocks;
	const {createElement} = wp.element;
	const {InspectorControls} = wp.blockEditor;
	const {TextControl, SelectControl, ToggleControl} = wp.components;

	registerBlockType( phpConfig.block.name, {
		title: phpConfig.block.title,
		category: phpConfig.block.category,
		icon: phpConfig.block.icon,
		initialOpen: 1,
		edit(props){
			const att = props.attributes;
			const setAtts = props.setAttributes;

			function changeField( val ){
				setAtts( {[this]: val} );
			}	

			function changeFieldMulti( val ){
				var myval = new Array(val);
				setAtts( {[this]: myval} );
			}	

			var ret = [];
			for ( var fieldname in phpConfig){
				switch( this.phpConfig[fieldname]['field_type'] ){
					case 'text': 
						ret.push( createElement( TextControl, { value: att[fieldname], label: this.phpConfig[fieldname]['label'], type: 'text', onChange: changeField.bind( fieldname ), required:'required' } ) );
						break;
					case 'select': 
						var opts = [];
						for ( var v in this.phpConfig[fieldname]['values'] ){
							opts.push( JSON.parse( '{"value":"' + v + '", "label":"' + this.phpConfig[fieldname]['values'][v] + '"}' ) );
						}
						ret.push( createElement( SelectControl, { value: att[fieldname], label: this.phpConfig[fieldname]['label'], type: 'text', onChange: changeField.bind( fieldname ), options: opts } ) );
						// ret.push( createElement( SelectControl, { multiple: 1, value: att[fieldname], label: this.phpConfig[fieldname]['label'], type: 'text', onChange: changeFieldMulti.bind( fieldname ), options: opts } ) );
						break;
					case 'toggle': 
						ret.push( createElement( ToggleControl, { checked: att[fieldname], label: this.phpConfig[fieldname]['label'], type: 'text', onChange: changeField.bind( fieldname ) } ) );
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