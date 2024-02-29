import { useState, useEffect } from '@wordpress/element';
import { PanelBody, DateTimePicker, CheckboxControl, FormTokenField, TextControl, SelectControl, RadioControl, ToggleControl, Button } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json'; // Import block.json metadata

export default function Edit({ attributes, setAttributes }) {
    const { provider, orgids, jobid, limit, orderby, order, fallback_apply, link_only, category, fauorg } = attributes;
    const blockProps = useBlockProps();

    // Initialize attributes with default values from block.json
    const defaultAttributes = {};
    Object.keys(metadata.attributes).forEach(key => {
        defaultAttributes[key] = metadata.attributes[key].default;
    });

    useEffect(() => {
        // Set default attributes when the component mounts
        setAttributes(defaultAttributes);
    }, []);

    const onProviderChange = (newProvider) => {
        setAttributes({ provider: newProvider });
    };

    const onOrgidsChange = (newOrgids) => {
        setAttributes({ orgids: newOrgids });
    };

    const onJobidChange = (newJobid) => {
        setAttributes({ jobid: newJobid });
    };

    const onLimitChange = (newLimit) => {
        setAttributes({ limit: newLimit });
    };

    const onOrderbyChange = (newOrderby) => {
        setAttributes({ orderby: newOrderby });
    };

    const onOrderChange = (newOrder) => {
        setAttributes({ order: newOrder });
    };

    const onFallbackApplyChange = (newFallbackApply) => {
        setAttributes({ fallback_apply: newFallbackApply });
    };

    const onLinkOnlyChange = (newLinkOnly) => {
        setAttributes({ link_only: newLinkOnly });
    };

    const onCategoryChange = (newCategory) => {
        setAttributes({ category: newCategory });
    };

    const onFauorgChange = (newFauorg) => {
        setAttributes({ fauorg: newFauorg });
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Filter', 'rrze-faq')}>

                    <SelectControl
                        label={__('Provider')}
                        value={provider}
                        options={[
                            { label: __('BITE'), value: 'bite' },
                            { label: __('Interamt'), value: 'interamt' },
                            { label: __('UnivIS'), value: 'univis' }
                        ]}
                        onChange={onProviderChange}
                    />
                    <TextControl
                        label={__('OrgID(s)')}
                        value={orgids}
                        onChange={onOrgidsChange}
                    />
                    <TextControl
                        label={__('Job ID (0 = all)')}
                        value={jobid}
                        type="number"
                        onChange={onJobidChange}
                    />
                    <TextControl
                        label={__('Number of job offers')}
                        value={limit}
                        type="number"
                        onChange={onLimitChange}
                    />
                    <SelectControl
                        label={__('Order by')}
                        value={orderby}
                        options={[
                            { label: __('Job title'), value: 'title' },
                            { label: __('Application end'), value: 'validThrough' },
                            { label: __('Job start'), value: 'jobStartDateSort' }
                        ]}
                        onChange={onOrderbyChange}
                    />
                    <RadioControl
                        label={__('Order')}
                        selected={order}
                        options={[
                            { label: __('Ascending'), value: 'ASC' },
                            { label: __('Descending'), value: 'DESC' }
                        ]}
                        onChange={onOrderChange}
                    />
                    <TextControl
                        label={__('Default application link')}
                        value={fallback_apply}
                        onChange={onFallbackApplyChange}
                    />
                    <ToggleControl
                        label={__('Show only links to BITE')}
                        checked={link_only}
                        onChange={onLinkOnlyChange}
                    />
                    <SelectControl
                        label={__('Filter by occupationalCategory')}
                        value={category}
                        options={[
                            { label: __('Research & Teaching'), value: 'wiss' },
                            { label: __('Technology & Administration'), value: 'n-wiss' },
                            { label: __('Trainee'), value: 'azubi' },
                            { label: __('Student assistants'), value: 'hiwi' },
                            { label: __('Professorships'), value: 'prof' },
                            { label: __('Other'), value: 'other' },
                            { label: __('No filter'), value: '' }
                        ]}
                        onChange={onCategoryChange}
                    />
                    <TextControl
                        label={__('FAU.ORG Number')}
                        value={fauorg}
                        type="number"
                        onChange={onFauorgChange}
                    />
                </PanelBody >
            </InspectorControls >

            <div {...blockProps}>
                <ServerSideRender
                    block="create-block/rrze-jobs"
                    attributes={attributes}
                />
            </div>

        </>
    );
};

