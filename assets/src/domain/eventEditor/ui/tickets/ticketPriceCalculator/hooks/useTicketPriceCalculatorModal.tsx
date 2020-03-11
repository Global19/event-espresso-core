// @ts-nocheck
import { useState, useEffect, useCallback } from 'react';
import { isEmpty, isNil } from 'ramda';
import { __, sprintf } from '@wordpress/i18n';

import useOnSubmitPrices from './useOnSubmitPrices';
import useTicketPriceCalculatorFormDecorator from './useTicketPriceCalculatorFormDecorator';
import useTicketPriceCalculatorFormMutators from './useTicketPriceCalculatorFormMutators';
import usePriceModifier from '../usePriceModifier';
import TicketPriceCalculatorForm from '../TicketPriceCalculatorForm';
import { TpcFormData } from '../types';
import useTicketPrices from '../../../../services/apollo/queries/tickets/useTicketPrices';
import useTicketItem from '../../../../services/apollo/queries/tickets/useTicketItem';
import defaultPrice from '../defaultPriceModifier';
import { sortByPriceOrderIdAsc } from '../../../../../shared/entities/prices/predicates/sortingPredicates';
import { copyPriceFields } from '../../../../../shared/entities/prices/predicates/updatePredicates';
import { copyTicketFields } from '../../../../../shared/entities/tickets/predicates/updatePredicates';
import { useFormModal, FormModal, ModalClose } from '@appLayout/formModal';

const INITIAL_STATE: TpcFormData = {
	ticket: null,
	prices: [],
};

const useTicketPriceCalculatorModal: FormModal = ({ entityId: ticketId }) => {
	const [initialValues, setInitialValues] = useState<TpcFormData>(INITIAL_STATE);
	const decorator = useTicketPriceCalculatorFormDecorator();
	const mutators = useTicketPriceCalculatorFormMutators();
	const prices = useTicketPrices(ticketId);
	const defaultPriceModifier = usePriceModifier(defaultPrice);
	const submitPrices = useOnSubmitPrices(prices);
	const ticket = useTicketItem({ id: ticketId });
	const { closeEditor } = useFormModal();

	useEffect(() => {
		const updatable =
			!isNil(ticket) && !isEmpty(ticket) && (isNil(initialValues.ticket) || isEmpty(initialValues.ticket));
		if (updatable) {
			const sortedPrices = sortByPriceOrderIdAsc(prices);
			sortedPrices.push(defaultPriceModifier);
			const formData = {
				ticket: copyTicketFields(ticket),
				prices: sortedPrices.map((price) => copyPriceFields(price)), // avoid passing index in .map() as 2nd param
			};
			setInitialValues(formData);
		}
	}, [prices, ticket]);

	const onClose = useCallback<ModalClose>((): void => {
		closeEditor('ticketPriceCalculator');
		setInitialValues(INITIAL_STATE);
	}, [closeEditor]);

	return {
		formComponent: TicketPriceCalculatorForm,
		onSubmit: submitPrices,
		initialValues,
		onClose,
		decorators: [decorator],
		mutators,
		title: sprintf(__('Price Calculator for Ticket: %s'), ticket?.name),
	};
};

export default useTicketPriceCalculatorModal;