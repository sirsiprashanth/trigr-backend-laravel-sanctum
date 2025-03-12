import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faCircle } from '@fortawesome/free-solid-svg-icons';

export default function CoachingPlanDetails({ plan, onBack }) {
    const renderGoalSection = (goalData) => {
        if (!goalData) return null;

        return (
            <div className="mb-8 bg-[#111315] rounded-xl p-6">
                <h3 className="text-lg font-unbounded-600 text-[#85C240] mb-4">
                    {goalData.focusAreaName}
                </h3>

                {/* Goal */}
                <div className="mb-4">
                    <div className="flex items-center space-x-2">
                        <span className="text-[#85C240]">⊕</span>
                        <h4 className="text-lg font-unbounded-600">GOAL</h4>
                    </div>
                    <p className="text-white ml-6 mt-2">{goalData.title}</p>
                </div>

                {/* Strategies */}
                {goalData.strategies && goalData.strategies.length > 0 && (
                    <div className="mb-4">
                        <div className="flex items-center space-x-2">
                            <span className="text-[#85C240]">💡</span>
                            <h4 className="text-lg font-unbounded-600">STRATEGIES</h4>
                        </div>
                        <ul className="ml-6 mt-2 space-y-2">
                            {goalData.strategies.map((strategy, index) => (
                                <li key={index} className="flex items-center space-x-2">
                                    <span className="text-[#85C240] text-xs">•</span>
                                    <span className="text-gray-300">{strategy.text}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Action Plans */}
                {goalData.actionPlans && goalData.actionPlans.length > 0 && (
                    <div>
                        <div className="flex items-center space-x-2">
                            <span className="text-[#85C240]">✓</span>
                            <h4 className="text-lg font-unbounded-600">ACTION PLANS</h4>
                        </div>
                        <ul className="ml-6 mt-2 space-y-2">
                            {goalData.actionPlans.map((action, index) => (
                                <li key={index} className="text-gray-300">
                                    <div className="flex items-center space-x-2">
                                        <span className="text-[#85C240] text-xs">•</span>
                                        <span>{action.text}</span>
                                    </div>
                                    <div className="ml-4 mt-1 text-sm text-gray-500">
                                        {action.frequency.toUpperCase()} - {action.selectedDays.join(', ')}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        );
    };

    return (
        <div className="flex-1 ml-64 mr-80">
            <div className="p-8">
                {/* Header */}
                <div className="flex items-center space-x-4 mb-8">
                    <button 
                        onClick={onBack}
                        className="text-gray-400 hover:text-white transition-colors"
                    >
                        <FontAwesomeIcon icon={faArrowLeft} />
                    </button>
                    <h1 className="text-4xl font-unbounded-900">PLAN DETAILS</h1>
                </div>

                {/* Plan Title and Info */}
                <div className="mb-8">
                    <h2 className="text-2xl font-unbounded-700 mb-4">{plan.title}</h2>
                    <div className="space-y-2 text-gray-400">
                        <p>START DATE: {new Date(plan.startDate).toLocaleDateString()}</p>
                        <p>END DATE: {new Date(plan.endDate).toLocaleDateString()}</p>
                        <p>PLAN CODE: {plan.planCode}</p>
                    </div>
                </div>

                {/* Goals Sections */}
                {plan.goals && Object.entries(plan.goals).map(([key, goalData]) => (
                    <div key={key}>
                        {renderGoalSection(goalData)}
                    </div>
                ))}
            </div>
        </div>
    );
} 