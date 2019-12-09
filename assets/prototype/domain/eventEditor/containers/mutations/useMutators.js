import useDatetimeMutator from './datetimes/useDatetimeMutator';
import useTicketMutator from './tickets/useTicketMutator';

const useMutators = () => {
	const datetimeMutator = useDatetimeMutator();
	const ticketMutator = useTicketMutator();
	// const priceMutator = usePriceMutator();

	return {
		datetimeMutator,
		ticketMutator,
		// priceMutator,
	};
};

export default useMutators;
