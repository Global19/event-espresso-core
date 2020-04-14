import React, { useCallback } from 'react';
import { __ } from '@wordpress/i18n';

import type { TicketItemProps } from '../types';
import { useTicketMutator } from '@edtrServices/apollo/mutations';
import { getPropsAreEqual } from '@appServices/utilities';
import CurrencyInput from '@appInputs/CurrencyInput';

interface EditablePriceProps extends TicketItemProps {
	className?: string;
}

const EditablePrice: React.FC<EditablePriceProps> = ({ entity: ticket, className }) => {
	const { updateEntity } = useTicketMutator(ticket.id);

	const onChangePrice = useCallback(
		({ amount: price }: any): void => {
			price = parseFloat(price);
			if (price !== ticket.price) {
				updateEntity({ price });
			}
		},
		[ticket.cacheId]
	);

	const inputProps = { ellipsis: false };
	const wrapperProps = { className };

	return (
		<CurrencyInput
			id={ticket.id}
			amount={ticket.price}
			placeholder={__('set price...')}
			inputProps={inputProps}
			wrapperProps={wrapperProps}
			onChange={onChangePrice}
			tag={'h3'}
		/>
	);
};

export default React.memo(EditablePrice, getPropsAreEqual(['entity', 'price']));