import React from 'react';
import classNames from 'classnames';
import { __ } from '@wordpress/i18n';

import datesListTableHeader from './datesListTableHeader';
import datesListTableRow from './datesListTableRow';
import { ResponsiveTable } from '@appLayout/espressoTable';
import { useDatetimeItem } from '@edtrServices/apollo/queries';

import { TableViewProps } from './types';

import './styles.scss';

const noZebraStripe = ['row', 'stripe', 'name', 'actions'];

/**
 * EditorDateEntitiesListView
 * Displays event date details in a standard list table like view
 */
const TableView: React.FC<TableViewProps> = ({ className, displayStartOrEndDate, entities: datetimes, ...props }) => {
	const formRows = datetimes.map(({ id }) => {
		const datetime = useDatetimeItem({ id });
		const columns = datesListTableRow({ datetime, displayStartOrEndDate, ...props });
		return columns;
	});

	const headerRows = datesListTableHeader(displayStartOrEndDate);
	const tableClassName = classNames(className, 'ee-dates-list-list-view');

	return (
		<ResponsiveTable
			bodyRows={formRows}
			className={{ tableClassName }}
			headerRows={[headerRows]}
			metaData={{
				tableId: 'date-entities-list-view',
				tableCaption: __('Event Dates', 'event_espresso'),
			}}
		/>
	);
};

export default TableView;