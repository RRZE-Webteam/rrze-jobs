function createBlock() {
	const { registerBlockType } = wp.blocks;
	const { createElement } = wp.element;
	const { InspectorControls }  = wp.blockEditor;
	const { CheckboxControl, RadioControl, SelectControl, TextControl, TextareaControl, ToggleControl } = wp.components;

	registerBlockType( phpConfig.block.name, {
		title: phpConfig.block.title,
		category: phpConfig.block.category,
		icon: phpConfig.block.icon,
		initialOpen: 1,
		edit( props ){
			const att = props.attributes;
			const setAtts = props.setAttributes;
			const config = this.phpConfig; // defined in config/config.php

			function changeField( val ){
				if ( config[this]['type'] == 'number' ){
					val = parseInt( val );
				}
				setAtts( {[this]: val} );
			}	

			var ret = [];
			ret.push( createElement( 'div', { className: 'components-placeholder__label' }, createElement( 'span', { className: 'editor-block-icon block-editor-block-icon dashicons-before dashicons-' + config.block.icon }, null ), config.block.title ) );

			for ( var fieldname in phpConfig ){
				switch( config[fieldname]['field_type'] ){
					case 'checkbox': 
						ret.push( createElement( CheckboxControl, { checked: ( typeof att[fieldname] !== 'undefined' ? att[fieldname] : config[fieldname]['checked'] ), label: config[fieldname]['label'], onChange: changeField.bind( fieldname ) } ) );
						break;
					case 'radio': 
						var opts = [];
						for ( var v in config[fieldname]['values'] ){
							opts.push( JSON.parse( '{"value":"' + v + '", "label":"' + config[fieldname]['values'][v] + '"}' ) );
						}
						ret.push( createElement( RadioControl, { selected: ( typeof att[fieldname] !== 'undefined' ? att[fieldname] : config[fieldname]['selected'] ), label: config[fieldname]['label'], onChange: changeField.bind( fieldname ), options: opts } ) );
						break;
					case 'multi_select': 
					case 'select': 
						var opts = [];
						for ( var v in config[fieldname]['values'] ){
							opts.push( JSON.parse( '{"value":"' + v + '", "label":"' + config[fieldname]['values'][v] + '"}' ) );
						}
						ret.push( createElement( SelectControl, { multiple: ( config[fieldname]['field_type'] == 'multi_select' ? 1 : 0 ), value: att[fieldname], label: config[fieldname]['label'], type: config[fieldname]['type'], onChange: changeField.bind( fieldname ), options: opts } ) );
						break;
					case 'text': 
						ret.push( createElement( TextControl, { value: att[fieldname], label: config[fieldname]['label'], type: config[fieldname]['type'], onChange: changeField.bind( fieldname ) } ) );
						break;
					case 'textarea': 
						ret.push( createElement( TextareaControl, { value: att[fieldname], label: config[fieldname]['label'], type: config[fieldname]['type'], onChange: changeField.bind( fieldname ) } ) );
						break;
					case 'toggle': 
						ret.push( createElement( ToggleControl, { checked: ( typeof att[fieldname] !== 'undefined' ? att[fieldname] : config[fieldname]['checked'] ), label: config[fieldname]['label'], type: config[fieldname]['type'], onChange: changeField.bind( fieldname ) } ) );
						break;
				}
			}

			if ( config.block.show_block == 'right'){
				return createElement('div', {}, [
					createElement( 'div', {}, phpConfig.block.message ),
					createElement( InspectorControls, {}, ret )
				] )
			}else{
				return createElement('div', { className: "components-placeholder" }, ret )
			}
		},
		save(){
			 return null;
		}	
	} );
}

createBlock();