import { useState, useEffect } from '@wordpress/element';
import { PanelBody, DateTimePicker, CheckboxControl, FormTokenField, TextControl, SelectControl, RadioControl, ToggleControl, Button } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json'; // Import block.json metadata

export default function Edit({ attributes, setAttributes }) {
    const { provider, orgids, jobid, limit, orderby, order, fallback_apply, default_subject, link_only, category, fauorg } = attributes;
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

    const onDefaultSubjectChange = (newDefaultSubject) => {
        setAttributes({ default_subject: newDefaultSubject });
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
                <PanelBody title={__('Filter', 'rrze-jobs')}>

                    <SelectControl
                        label={__('Provider', 'rrze-jobs')}
                        value={provider}
                        options={[
                            { label: __('BITE', 'rrze-jobs'), value: 'bite' },
                            { label: __('Interamt', 'rrze-jobs'), value: 'interamt' },
                            { label: __('UnivIS', 'rrze-jobs'), value: 'univis' }
                        ]}
                        onChange={onProviderChange}
                    />
                    <TextControl
                        label={__('OrgID(s)', 'rrze-jobs')}
                        value={orgids}
                        onChange={onOrgidsChange}
                    />
                    <TextControl
                        label={__('Job ID (0 = all)', 'rrze-jobs')}
                        value={jobid}
                        type="number"
                        onChange={onJobidChange}
                    />
                    <TextControl
                        label={__('Number of job offers', 'rrze-jobs')}
                        value={limit}
                        type="number"
                        onChange={onLimitChange}
                    />
                    <SelectControl
                        label={__('Order by', 'rrze-jobs')}
                        value={orderby}
                        options={[
                            { label: __('Job title', 'rrze-jobs'), value: 'title' },
                            { label: __('Application end', 'rrze-jobs'), value: 'validThrough' },
                            { label: __('Job start', 'rrze-jobs'), value: 'jobStartDateSort' }
                        ]}
                        onChange={onOrderbyChange}
                    />
                    <RadioControl
                        label={__('Order', 'rrze-jobs')}
                        selected={order}
                        options={[
                            { label: __('Ascending', 'rrze-jobs'), value: 'ASC' },
                            { label: __('Descending', 'rrze-jobs'), value: 'DESC' }
                        ]}
                        onChange={onOrderChange}
                    />
                    <TextControl
                        label={__('Default application link', 'rrze-jobs')}
                        value={fallback_apply}
                        onChange={onFallbackApplyChange}
                    />
                    <TextControl
                        label={__('Default application email subject', 'rrze-jobs')}
                        value={default_subject}
                        onChange={onDefaultSubjectChange}
                    />
                    <ToggleControl
                        label={__('Show only links to BITE', 'rrze-jobs')}
                        checked={link_only}
                        onChange={onLinkOnlyChange}
                    />
                    <SelectControl
                        label={__('Filter by occupationalCategory', 'rrze-jobs')}
                        value={category}
                        options={[
                            { label: __('Research & Teaching', 'rrze-jobs'), value: 'wiss' },
                            { label: __('Technology & Administration', 'rrze-jobs'), value: 'n-wiss' },
                            { label: __('Trainee', 'rrze-jobs'), value: 'azubi' },
                            { label: __('Student assistants', 'rrze-jobs'), value: 'hiwi' },
                            { label: __('Professorships', 'rrze-jobs'), value: 'prof' },
                            { label: __('Other', 'rrze-jobs'), value: 'other' },
                            { label: __('No filter', 'rrze-jobs'), value: '' }
                        ]}
                        onChange={onCategoryChange}
                    />
                    <TextControl
                        label={__('FAU.ORG Number', 'rrze-jobs')}
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

