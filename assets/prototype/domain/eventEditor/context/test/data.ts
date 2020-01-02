import '../../types';
import { EventData } from '../../types';
import { RelationalData } from '../../../../application/services/apollo/relations';
import { nodes as datetimes } from '../../data/queries/datetimes/test/data';
import { nodes as tickets } from '../../data/queries/tickets/test/data';
import { nodes as prices } from '../../data/queries/prices/test/data';
import { nodes as priceTypes } from '../../data/queries/priceTypes/test/data';

export const eventId: number = 100;

/**
 * See the structure of returned data in `/docs/GraphQL-API/query/eventRelations.md`
 */
export const relationalData: RelationalData = {
	datetimes: {
		[datetimes[0].id]: {
			tickets: [tickets[0].id, tickets[1].id],
		},
		[datetimes[1].id]: {
			tickets: [tickets[1].id],
		},
	},
	tickets: {
		[tickets[0].id]: {
			datetimes: [datetimes[0].id],
			prices: [prices[0].id, prices[2].id],
		},
		[tickets[1].id]: {
			datetimes: [datetimes[0].id, datetimes[1].id],
			prices: [prices[1].id],
		},
	},
	prices: {
		[prices[0].id]: {
			tickets: [tickets[0].id],
			priceTypes: [priceTypes[2].id],
		},
		[prices[1].id]: {
			tickets: [tickets[1].id],
			priceTypes: [priceTypes[0].id],
		},
		[prices[2].id]: {
			tickets: [tickets[0].id],
			priceTypes: [priceTypes[3].id],
		},
	},
};

// Add only what's needed
export const event: EventData = {
	dbId: eventId,
	relations: relationalData,
};
